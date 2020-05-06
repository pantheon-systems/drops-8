<?php

namespace Drupal\Tests\webform\Functional\Handler;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\webform\Entity\Webform;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for email webform handler rendering functionality.
 *
 * @group Webform
 */
class WebformHandlerEmailRenderingTest extends WebformBrowserTestBase {

  use AssertMailTrait;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Make sure we are using distinct default and administrative themes for
    // the duration of these tests.
    \Drupal::service('theme_handler')->install(['webform_test_bartik', 'seven']);
    $this->config('system.theme')
      ->set('default', 'webform_test_bartik')
      ->set('admin', 'seven')
      ->save();
  }

  /**
   * Test email handler rendering.
   */
  public function testEmailRendering() {
    $this->drupalLogin($this->rootUser);

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('contact');

    // Check that we are currently using the bartik.theme.
    $this->drupalGet('/webform/contact');
    $this->assertRaw('core/themes/bartik/css/base/elements.css');

    // Post submission and send emails.
    $edit = [
      'name' => 'Dixisset',
      'email' => 'test@test.com',
      'subject' => 'Testing contact webform from [site:name]',
      'message' => 'Please ignore this email.',
    ];
    $this->postSubmission($webform, $edit);

    // Check submitting contact form and sending emails using the
    // default bartik.theme.
    $sent_emails = $this->getMails();
    $this->assertContains('HEADER 1 (CONTACT_EMAIL_CONFIRMATION)', $sent_emails[0]['body']);
    $this->assertContains('Please ignore this email.', $sent_emails[0]['body']);
    $this->assertContains('address (contact_email_confirmation)', $sent_emails[0]['body']);
    $this->assertContains('HEADER 1 (GLOBAL)', $sent_emails[1]['body']);
    $this->assertContains('Please ignore this email.', $sent_emails[1]['body']);
    $this->assertContains('address (global)', $sent_emails[1]['body']);

    // Disable dedicated page which will cause the form to now use the
    // seven.theme.
    // @see \Drupal\webform\Theme\WebformThemeNegotiator
    $webform->setSetting('page', FALSE);
    $webform->save();

    // Check that we are now using the seven.theme.
    $this->drupalGet('/webform/contact');
    $this->assertNoRaw('core/themes/bartik/css/base/elements.css');

    // Post submission and send emails.
    $this->postSubmission($webform, $edit);

    // Check submitting contact form and sending emails using the
    // seven.theme but the rendered the emails still use the default
    // bartik.theme.
    // @see \Drupal\webform\Plugin\WebformHandler\EmailWebformHandler::getMessage
    $sent_emails = $this->getMails();
    $this->assertContains('HEADER 1 (CONTACT_EMAIL_CONFIRMATION)', $sent_emails[2]['body']);
    $this->assertContains('Please ignore this email.', $sent_emails[2]['body']);
    $this->assertContains('address (contact_email_confirmation)', $sent_emails[2]['body']);
    $this->assertContains('HEADER 1 (GLOBAL)', $sent_emails[3]['body']);
    $this->assertContains('Please ignore this email.', $sent_emails[3]['body']);
    $this->assertContains('address (global)', $sent_emails[3]['body']);
  }

}
