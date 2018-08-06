<?php

namespace Drupal\webform\Tests\Settings;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform submission serial number.
 *
 * @group Webform
 */
class WebformSettingsSerialTest extends WebformTestBase {

  /**
   * Tests webform submission serial number.
   */
  public function testSettings() {
    // Login the admin user.
    $this->drupalLogin($this->rootUser);

    $webform_contact = Webform::load('contact');

    // Set next serial to 99.
    $this->drupalPostForm('admin/structure/webform/manage/contact/settings/submissions', ['next_serial' => 99], t('Save'));

    // Check next serial is 99.
    $sid = $this->postSubmissionTest($webform_contact);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEqual($webform_submission->serial(), 99);

    // Check that next serial is set to max serial.
    $this->drupalPostForm('admin/structure/webform/manage/contact/settings/submissions', ['next_serial' => 1], t('Save'));
    $this->assertRaw('The next submission number was increased to 100 to make it higher than existing submissions.');
  }

}
