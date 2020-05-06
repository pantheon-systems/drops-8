<?php

namespace Drupal\Tests\webform\Functional\Handler;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for the webform handler invoke alter hook.
 *
 * @group Webform
 */
class WebformHandlerInvokeAlterHookTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'webform_test_handler_invoke_alter'];

  /**
   * Tests webform handler invoke alter hook.
   */
  public function testWebformHandlerInvokeAlterHook() {
    // Check invoke alter hooks.
    $this->drupalGet('/webform/contact');
    $this->assertRaw('Invoking hook_webform_handler_invoke_alter() for "contact:email_confirmation::pre_create"');
    $this->assertRaw('Invoking hook_webform_handler_invoke_pre_create_alter() for "contact:email_confirmation"');
    $this->assertRaw('Invoking hook_webform_handler_invoke_alter() for "contact:email_notification::pre_create"');
    $this->assertRaw('Invoking hook_webform_handler_invoke_pre_create_alter() for "contact:email_notification"');
    $this->assertRaw('Invoking hook_webform_handler_invoke_alter() for "contact:email_confirmation::alter_elements"');
    $this->assertRaw('Invoking hook_webform_handler_invoke_alter() for "contact:email_notification::alter_elements"');
    $this->assertRaw('Invoking hook_webform_handler_invoke_alter() for "contact:email_confirmation::alter_elements"');
    $this->assertRaw('Invoking hook_webform_handler_invoke_alter() for "contact:email_notification::alter_elements"');
    $this->assertRaw('Invoking hook_webform_handler_invoke_alter() for "contact:email_confirmation::post_create"');
    $this->assertRaw('Invoking hook_webform_handler_invoke_alter() for "contact:email_notification::post_create"');
    $this->assertRaw('Invoking hook_webform_handler_invoke_alter() for "contact:email_confirmation::override_settings"');
    $this->assertRaw('Invoking hook_webform_handler_invoke_alter() for "contact:email_notification::override_settings"');
    $this->assertRaw('Invoking hook_webform_handler_invoke_alter() for "contact:email_confirmation::alter_form"');
    $this->assertRaw('Invoking hook_webform_handler_invoke_alter() for "contact:email_notification::alter_form"');
  }

}
