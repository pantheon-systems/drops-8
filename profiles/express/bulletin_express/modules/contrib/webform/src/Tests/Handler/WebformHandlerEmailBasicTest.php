<?php

namespace Drupal\webform\Tests\Handler;

use Drupal\webform\Element\WebformSelectOther;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for basic email webform handler functionality.
 *
 * @group Webform
 */
class WebformHandlerEmailBasicTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_handler_email'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create users.
    $this->createUsers();
  }

  /**
   * Test basic email handler.
   */
  public function testBasicEmailHandler() {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_handler_email');

    // Create a submission using the test webform's default values.
    $this->postSubmission($webform);

    // Check sending a basic email via a submission.
    $sent_email = $this->getLastEmail();
    $this->assertEqual($sent_email['reply-to'], "John Smith <from@example.com>");
    $this->assertContains($sent_email['body'], 'Submitted by: Anonymous');
    $this->assertContains($sent_email['body'], 'First name: John');
    $this->assertContains($sent_email['body'], 'Last name: Smith');
    $this->assertEqual($sent_email['headers']['From'], 'John Smith <from@example.com>');
    $this->assertEqual($sent_email['headers']['Cc'], 'cc@example.com');
    $this->assertEqual($sent_email['headers']['Bcc'], 'bcc@example.com');

    // Check sending with the saving of results disabled.
    $webform->setSetting('results_disabled', TRUE)->save();
    $this->postSubmission($webform, ['first_name' => 'Jane', 'last_name' => 'Doe']);
    $sent_email = $this->getLastEmail();
    $this->assertContains($sent_email['body'], 'First name: Jane');
    $this->assertContains($sent_email['body'], 'Last name: Doe');
    $webform->setSetting('results_disabled', FALSE)->save();

    // Check sending a custom email using tokens.
    $this->drupalLogin($this->adminWebformUser);
    $body = implode(PHP_EOL, [
      'full name: [webform_submission:values:first_name] [webform_submission:values:last_name]',
      'uuid: [webform_submission:uuid]',
      'sid: [webform_submission:sid]',
      'date: [webform_submission:created]',
      'ip-address: [webform_submission:ip-address]',
      'user: [webform_submission:user]',
      'url: [webform_submission:url]',
      'edit-url: [webform_submission:url:edit-form]',
      'Test that "double quotes" are not encoded.',
    ]);

    $this->drupalPostForm('admin/structure/webform/manage/test_handler_email/handlers/email/edit', ['settings[body]' => WebformSelectOther::OTHER_OPTION, 'settings[body_custom_text]' => $body], t('Save'));

    $sid = $this->postSubmission($webform);
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = WebformSubmission::load($sid);

    $sent_email = $this->getLastEmail();
    $this->assertContains($sent_email['body'], 'full name: John Smith');
    $this->assertContains($sent_email['body'], 'uuid: ' . $webform_submission->uuid->value);
    $this->assertContains($sent_email['body'], 'sid: ' . $sid);
    $this->assertContains($sent_email['body'], 'date: ' . \Drupal::service('date.formatter')->format($webform_submission->created->value, 'medium'));
    $this->assertContains($sent_email['body'], 'ip-address: ' . $webform_submission->remote_addr->value);
    $this->assertContains($sent_email['body'], 'user: ' . $this->adminWebformUser->label());
    $this->assertContains($sent_email['body'], "url:");
    $this->assertContains($sent_email['body'], $webform_submission->toUrl('canonical', ['absolute' => TRUE])->toString());
    $this->assertContains($sent_email['body'], "edit-url:");
    $this->assertContains($sent_email['body'], $webform_submission->toUrl('edit-form', ['absolute' => TRUE])->toString());
    $this->assertContains($sent_email['body'], 'Test that "double quotes" are not encoded.');
  }

}
