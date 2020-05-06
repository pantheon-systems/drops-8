<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionForm;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for disable tracking of remote IP address.
 *
 * @group Webform
 */
class WebformSettingsRemoteAddrTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_remote_addr'];

  /**
   * Tests webform disable remote IP address.
   */
  public function testRemoteAddr() {
    $this->drupalLogin($this->rootUser);

    // Get submission values and data.
    $values = [
      'webform_id' => 'test_form_remote_addr',
      'data' => [
        'name' => 'John',
      ],
    ];

    // Make sure the IP is not stored.
    $webform = Webform::load('test_form_remote_addr');
    $sid = $this->postSubmission($webform, ['name' => 'John']);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEqual($webform_submission->getRemoteAddr(), t('(unknown)'));
    $this->assertEqual($webform_submission->getOwnerId(), 1);

    $webform_submission = WebformSubmissionForm::submitFormValues($values);
    $this->assertEqual($webform_submission->getRemoteAddr(), t('(unknown)'));
    $this->assertEqual($webform_submission->getOwnerId(), 1);

    // Enable the setting and make sure the IP is stored.
    $webform->setSetting('form_remote_addr', TRUE);
    $webform->save();
    $sid = $this->postSubmission($webform, ['name' => 'John']);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertNotEqual($webform_submission->getRemoteAddr(), t('(unknown)'));
    $this->assertEqual($webform_submission->getOwnerId(), 1);

    $webform_submission = WebformSubmissionForm::submitFormValues($values);
    $this->assertNotEqual($webform_submission->getRemoteAddr(), t('(unknown)'));
    $this->assertEqual($webform_submission->getOwnerId(), 1);
  }

}
