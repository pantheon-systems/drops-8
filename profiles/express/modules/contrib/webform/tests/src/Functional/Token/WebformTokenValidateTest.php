<?php

namespace Drupal\Tests\webform\Functional\Token;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform token element validation.
 *
 * @group Webform
 */
class WebformTokenValidateTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['token'];

  /**
   * Test webform token element validation.
   */
  public function testWebformTokenValidate() {
    $this->drupalLogin($this->rootUser);

    // Check invalid token validation.
    $this->drupalPostForm('/admin/structure/webform/config', ['form_settings[default_form_open_message][value]' => '[webform:invalid]'], t('Save configuration'));
    $this->assertRaw('invalid tokens');
    $this->assertRaw('<em class="placeholder">Default open message</em> is using the following invalid tokens: [webform:invalid].');

    // Check valid token validation.
    $this->drupalPostForm('/admin/structure/webform/config', ['form_settings[default_form_open_message][value]' => '[webform:title]'], t('Save configuration'));
    $this->assertNoRaw('invalid tokens');
  }

}
