<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform submission generator.
 *
 * @group Webform
 */
class WebformSubmissionGenerateTest extends WebformBrowserTestBase {

  /**
   * Tests webform submission entity.
   */
  public function testWebformSubmissionGenerate() {
    $this->drupalLogin($this->rootUser);

    $webform = Webform::load('contact');

    /**************************************************************************/
    // Test tab.
    /**************************************************************************/

    // Check test form.
    $sid = $this->postSubmissionTest($webform);
    $webform_submission = WebformSubmission::load($sid);
    // Note that only 'message' and 'subject' have predefined #test values.
    $test_data = [
      'message' => 'Please ignore this email.',
      'subject' => 'Testing contact webform from Drupal',
    ];
    $data = $webform_submission->getData();
    $this->assertEqual($data['message'], $test_data['message']);
    $this->assertEqual($data['subject'], $test_data['subject']);

    // Check test form classes and values.
    $this->drupalGet('/webform/contact/test');
    $this->assertCssSelect('.webform-submission-form.webform-submission-test-form.webform-submission-contact-form.webform-submission-contact-test-form');
    foreach ($test_data as $name => $value) {
      $this->assertFieldByName($name, $value);
    }

    /**************************************************************************/
    // Test querystring parameter.
    /**************************************************************************/

    // Check add form classes and empty values.
    $this->drupalGet('/webform/contact');
    $this->assertCssSelect('.webform-submission-form.webform-submission-add-form.webform-submission-contact-form.webform-submission-contact-add-form');
    foreach ($test_data as $name => $value) {
      $this->assertNoFieldByName($name, $value);
    }

    // Check add form classes and values with querystring parameter.
    $this->drupalGet('/webform/contact', ['query' => ['_webform_test' => 'contact']]);
    $this->assertCssSelect('.webform-submission-form.webform-submission-test-form.webform-submission-contact-form.webform-submission-contact-test-form');
    foreach ($test_data as $name => $value) {
      $this->assertFieldByName($name, $value);
    }
  }

}
