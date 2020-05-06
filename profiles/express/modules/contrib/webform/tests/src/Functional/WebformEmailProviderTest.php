<?php

namespace Drupal\Tests\webform\Functional;

/**
 * Tests for webform email provider.
 *
 * @group Webform
 */
class WebformEmailProviderTest extends WebformBrowserTestBase {

  /**
   * Test webform email provider.
   */
  public function testEmailProvider() {
    // Revert system.mail back to  php_mail.
    $this->container->get('config.factory')
      ->getEditable('system.mail')
      ->set('interface.default', 'php_mail')
      ->save();

    /** @var \Drupal\webform\WebformEmailProviderInterface $email_provider */
    $email_provider = \Drupal::service('webform.email_provider');

    $this->drupalLogin($this->rootUser);

    // Check Default PHP mailer is enabled because we manually changed the
    // system.mail configuration.
    $this->drupalGet('/admin/reports/status');
    $this->assertRaw('Provided by php_mail mail plugin.');
    $this->assertNoRaw("Webform PHP mailer: Sends the message as plain text or HTML, using PHP's native mail() function.");
    $this->assertRaw('Default PHP mailer: Sends the message as plain text, using PHP\'s native mail() function.');

    // Check Webform PHP mailer enabled after email provider check.
    $email_provider->check();
    $this->drupalGet('/admin/reports/status');
    $this->assertRaw('Provided by the Webform module.');
    $this->assertRaw("Webform PHP mailer: Sends the message as plain text or HTML, using PHP's native mail() function.");

    /**************************************************************************/
    // Mail System.
    /**************************************************************************/

    // Install mailsystem.module.
    \Drupal::service('module_installer')->install(['mailsystem']);

    // Check Mail System: Default PHP mailer after mailsystem.module installed.
    $this->drupalGet('/admin/reports/status');
    $this->assertRaw('Provided by the Mail System module.');
    $this->assertNoRaw("Webform PHP mailer: Sends the message as plain text or HTML, using PHP's native mail() function.");
    $this->assertRaw('Default PHP mailer: Sends the message as plain text, using PHP\'s native mail() function.');

    // Check Webform PHP mailer enabled after mailsystem module uninstalled.
    \Drupal::service('module_installer')->uninstall(['mailsystem']);
    $this->drupalGet('/admin/reports/status');
    $this->assertRaw("Webform PHP mailer: Sends the message as plain text or HTML, using PHP's native mail() function.");

    // Uninstall mailsystem.module.
    \Drupal::service('module_installer')->uninstall(['mailsystem']);

    /**************************************************************************/
    // SMTP.
    /**************************************************************************/

    // Install smtp.module.
    \Drupal::service('module_installer')->install(['smtp']);

    // Check Webform: Default PHP mailer after smtp.module installed
    // but still turned off.
    $this->drupalGet('/admin/reports/status');
    $this->assertRaw('Provided by the Webform module.');

    // Turn on the smtp.module via the UI.
    // @see webform_form_smtp_admin_settings_alter()
    $this->drupalPostForm('/admin/config/system/smtp', ['smtp_on' => TRUE], t('Save configuration'));

    // Check SMTP: Default PHP mailer after smtp.module turned on.
    $this->drupalGet('/admin/reports/status');
    $this->assertNoRaw('Provided by the SMTP module.');
  }

}
