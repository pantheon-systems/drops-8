<?php

namespace Drupal\webform\Tests\Settings;

use Drupal\Component\Serialization\Yaml;
use Drupal\webform\Tests\WebformTestBase;
use Drupal\webform\Utility\WebformYaml;

/**
 * Tests for webform entity.
 *
 * @group Webform
 */
class WebformSettingsAdminTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'webform', 'webform_ui'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element'];

  /**
   * Tests webform admin settings.
   */
  public function testAdminSettings() {
    global $base_path;

    $this->drupalLogin($this->rootUser);

    /* Settings Webform */

    // Get 'webform.settings'.
    $original_data = \Drupal::configFactory()->getEditable('webform.settings')->getRawData();

    // Update 'settings.default_form_close_message'.
    $types = [
      'forms' => 'admin/structure/webform/config',
      'elements' => 'admin/structure/webform/config/elements',
      'submissions' => 'admin/structure/webform/config/submissions',
      'handlers' => 'admin/structure/webform/config/handlers',
      'exporters' => 'admin/structure/webform/config/exporters',
      'libraries' => 'admin/structure/webform/config/libraries',
      'advanced' => 'admin/structure/webform/config/advanced',
    ];
    foreach ($types as $path) {
      $this->drupalPostForm($path, [], t('Save configuration'));
      \Drupal::configFactory()->reset('webform.settings');
      $updated_data = \Drupal::configFactory()->getEditable('webform.settings')->getRawData();

      // Check the updating 'Settings' via the UI did not lose or change any data.
      $this->assertEqual($updated_data, $original_data, 'Updated admin settings via the UI did not lose or change any data');

      // DEBUG:
      $this->verbose('<pre>' . WebformYaml::tidy(Yaml::encode($original_data)) . '</pre>');
      $this->verbose('<pre>' . WebformYaml::tidy(Yaml::encode($updated_data)) . '</pre>');
    }

    /* Elements */

    // Check that description is 'after' the element.
    $this->drupalGet('webform/test_element');
    $this->assertPattern('#\{item title\}.+\{item markup\}.+\{item description\}#ms');

    // Set the default description display to 'before'.
    $this->drupalPostForm('admin/structure/webform/config/elements', ['element[default_description_display]' => 'before'], t('Save configuration'));

    // Check that description is 'before' the element.
    $this->drupalGet('webform/test_element');
    $this->assertNoPattern('#\{item title\}.+\{item markup\}.+\{item description\}#ms');
    $this->assertPattern('#\{item title\}.+\{item description\}.+\{item markup\}#ms');

    /* UI disable dialog */

    // Check that dialogs are enabled.
    $this->drupalGet('admin/structure/webform');
    $this->assertRaw('<a href="' . $base_path . 'admin/structure/webform/add" class="button button-action button--primary button--small webform-ajax-link" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:700,&quot;dialogClass&quot;:&quot;webform-modal&quot;}">Add webform</a>');

    // Disable dialogs.
    $this->drupalPostForm('admin/structure/webform/config/advanced', ['ui[dialog_disabled]' => TRUE], t('Save configuration'));

    // Check that dialogs are disabled. (i.e. use-ajax is not included)
    $this->drupalGet('admin/structure/webform');
    $this->assertNoRaw('<a href="' . $base_path . 'admin/structure/webform/add" class="button button-action button--primary button--small webform-ajax-link" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:700,&quot;dialogClass&quot;:&quot;webform-modal&quot;}">Add webform</a>');
    $this->assertRaw('<a href="' . $base_path . 'admin/structure/webform/add" class="button button-action button--primary button--small">Add webform</a>');

    /* UI description help */

    // Check moving #description to #help for webform admin routes.
    $this->drupalPostForm('admin/structure/webform/config/advanced', ['ui[description_help]' => TRUE], t('Save configuration'));
    $this->assertRaw('<a href="#help" title="If checked, all element descriptions will be moved to help text (tooltip)." data-webform-help="If checked, all element descriptions will be moved to help text (tooltip)." class="webform-element-help">?</a>');

    // Check moving #description to #help for webform admin routes.
    $this->drupalPostForm('admin/structure/webform/config/advanced', ['ui[description_help]' => FALSE], t('Save configuration'));
    $this->assertNoRaw('<a href="#help" title="If checked, all element descriptions will be moved to help text (tooltip)." data-webform-help="If checked, all element descriptions will be moved to help text (tooltip)." class="webform-element-help">?</a>');
  }

}
