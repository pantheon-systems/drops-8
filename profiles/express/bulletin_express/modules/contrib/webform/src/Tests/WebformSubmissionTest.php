<?php

namespace Drupal\webform\Tests;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform submission entity.
 *
 * @group Webform
 */
class WebformSubmissionTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_results'];

  /**
   * Tests webform submission entity.
   */
  public function testWebformSubmission() {
    /** @var \Drupal\webform\WebformInterface $webform */
    /** @var \Drupal\webform\WebformSubmissionInterface[] $submissions */
    list($webform, $submissions) = $this->createWebformWithSubmissions();

    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = reset($submissions);

    // Check create submission.
    $this->assert($webform_submission instanceof WebformSubmission, '$webform_submission instanceof WebformSubmission');

    // Check get webform.
    $this->assertEqual($webform_submission->getWebform()->id(), $webform->id());

    // Check that YAML source entity is NULL.
    $this->assertNull($webform_submission->getSourceEntity());

    // Check get YAML source URL without uri, which will still return
    // the webform.
    $webform_submission
      ->set('uri', NULL)
      ->save();
    $this->assertEqual($webform_submission->getSourceUrl()->toString(), $webform->toUrl('canonical', ['absolute' => TRUE])->toString());

    // Check get YAML source URL set to user 1.
    $this->createUsers();
    $webform_submission
      ->set('entity_type', 'user')
      ->set('entity_id', $this->normalUser->id())
      ->save();
    $this->assertEqual($webform_submission->getSourceUrl()->toString(), $this->normalUser->toUrl('canonical', ['absolute' => TRUE])->toString());

    // Check missing webform_id exception.
    try {
      WebformSubmission::create();
      $this->fail('Webform id (webform_id) is required to create a webform submission.');
    }
    catch (\Exception $exception) {
      $this->pass('Webform id (webform_id) is required to create a webform submission.');
    }

    // Check creating a submission with default data.
    $webform_submission = WebformSubmission::create(['webform_id' => $webform->id(), 'data' => ['custom' => 'value']]);
    $this->assertEqual($webform_submission->getData(), ['custom' => 'value']);

    // Check submission label.
    $webform_submission->save();
    $this->assertEqual($webform_submission->label(), $webform->label() . ': Submission #' . $webform_submission->serial());
  }

  /**
   * Tests duplicating webform submission.
   */
  public function testDuplicateWebformSubmission() {
    $this->createUsers();
    $this->drupalLogin($this->adminSubmissionUser);

    $webform = Webform::load('contact');
    $sid = $this->postSubmission($webform, [
      'subject' => '{Original Subject}',
      'message' => '{Original Message}',
    ]);

    // Check duplicate form title.
    $this->drupalGet("admin/structure/webform/manage/contact/submission/$sid/duplicate");
    $this->assertRaw('Duplicate Contact: Submission #' . $sid);

    // Duplicate submission.
    $this->drupalPostForm("admin/structure/webform/manage/contact/submission/$sid/duplicate", ['subject' => '{Duplicate Subject}'], t('Send message'));
    $duplicate_sid = $this->getLastSubmissionId($webform);
    /** @var \Drupal\webform\WebformSubmissionInterface $duplicate_submission */
    $duplicate_submission = WebformSubmission::load($duplicate_sid);

    // Check duplicate submission.
    $this->assertNotEqual($sid, $duplicate_sid);
    $this->assertEqual($duplicate_submission->getData('subject'), '{Duplicate Subject}');
    $this->assertEqual($duplicate_submission->getData('message'), '{Original Message}');
  }

}
