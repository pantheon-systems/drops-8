<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformInterface;

/**
 * Tests for webform element.
 *
 * @group Webform
 */
class WebformElementTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform_test_element'];

  /**
   * Tests webform element.
   */
  public function testWebform() {
    $webform = Webform::load('contact');

    // Check webform render.
    $this->drupalGet('/webform_test_element');
    $this->assertFieldByName('email', '');
    $this->assertFieldByName('name', '');
    $this->assertFieldByName('subject', '');
    $this->assertFieldByName('message', '');

    // Check webform default data.
    $this->drupalGet('/webform_test_element', ['query' => ['default_data' => 'email: test']]);
    $this->assertFieldByName('email', 'test');

    // Check webform action.
    $this->drupalGet('/webform_test_element', ['query' => ['action' => 'http://drupal.org']]);
    $this->assertRaw('action="http://drupal.org"');

    // Check webform submit.
    $edit = [
      'email' => 'example@example.com',
      'name' => '{name}',
      'subject' => '{subject}',
      'message' => '{message}',
    ];
    $this->drupalPostForm('/webform_test_element', $edit, t('Send message'));
    $this->assertUrl('/');
    $this->assertRaw('Your message has been sent.');

    // Get last submission id.
    $sid = $this->getLastSubmissionId($webform);

    // Check submission is not render.
    $this->drupalGet('/webform_test_element', ['query' => ['sid' => $sid]]);
    $this->assertNoFieldByName('email', 'example@example.com');

    // Set webform access denied to display a message, instead of nothing.
    $webform->setSetting('form_access_denied', WebformInterface::ACCESS_DENIED_MESSAGE);
    $webform->save();

    // Check submission access denied message is displayed.
    $this->drupalGet('/webform_test_element', ['query' => ['sid' => $sid]]);
    $this->assertRaw("Please login to access this form.");

    // Login as root.
    $this->drupalLogin($this->rootUser);

    // Check submission can be edited.
    $this->drupalGet('/webform_test_element', ['query' => ['sid' => $sid]]);
    $this->assertFieldByName('email', 'example@example.com');
    $this->assertFieldByName('name', '{name}');
    $this->assertFieldByName('subject', '{subject}');
    $this->assertFieldByName('message', '{message}');
    $this->assertRaw('Submission information');

    // Check submission information is hidden.
    $this->drupalGet('/webform_test_element', ['query' => ['sid' => $sid, 'information' => 'false']]);
    $this->assertNoRaw('Submission information');
  }

}
