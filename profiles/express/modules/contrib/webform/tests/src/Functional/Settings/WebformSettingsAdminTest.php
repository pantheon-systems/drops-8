<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Utility\WebformYaml;

/**
 * Tests for webform entity.
 *
 * @group Webform
 */
class WebformSettingsAdminTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'captcha', 'node', 'views', 'webform', 'webform_ui', 'webform_node'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_actions_block');
  }

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
      $this->ksort($updated_data);

      // Check the updating 'Settings' via the UI did not lose or change any data.
      $this->assertEqual($updated_data, $original_data, 'Updated admin settings via the UI did not lose or change any data');

      // DEBUG:
      $original_yaml = WebformYaml::encode($original_data);
      $updated_yaml = WebformYaml::encode($updated_data);
      $this->verbose('<pre>' . $original_yaml . '</pre>');
      $this->verbose('<pre>' . $updated_yaml . '</pre>');
      $this->debug(array_diff(explode(PHP_EOL, $original_yaml), explode(PHP_EOL, $updated_yaml)));
    }

    /* Elements */

    // Check that description is 'after' the element.
    $this->drupalGet('/webform/test_element');
    $this->assertPattern('#\{item title\}.+\{item markup\}.+\{item description\}#ms');

    // Set the default description display to 'before'.
    $this->drupalPostForm('/admin/structure/webform/config/elements', ['element[default_description_display]' => 'before'], t('Save configuration'));

    // Check that description is 'before' the element.
    $this->drupalGet('/webform/test_element');
    $this->assertNoPattern('#\{item title\}.+\{item markup\}.+\{item description\}#ms');
    $this->assertPattern('#\{item title\}.+\{item description\}.+\{item markup\}#ms');

    /* UI disable dialog */

    // Check that dialogs are enabled.
    $this->drupalGet('/admin/structure/webform');
    $this->assertRaw('<a href="' . $base_path . 'admin/structure/webform/add" class="webform-ajax-link button button-action" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:700,&quot;dialogClass&quot;:&quot;webform-ui-dialog&quot;}" data-drupal-link-system-path="admin/structure/webform/add">Add webform</a>');

    // Disable dialogs.
    $this->drupalPostForm('/admin/structure/webform/config/advanced', ['ui[dialog_disabled]' => TRUE], t('Save configuration'));

    // Check that dialogs are disabled. (i.e. use-ajax is not included)
    $this->drupalGet('/admin/structure/webform');
    $this->assertNoRaw('<a href="' . $base_path . 'admin/structure/webform/add" class="webform-ajax-link button button-action" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:700,&quot;dialogClass&quot;:&quot;webform-ui-dialog&quot;}" data-drupal-link-system-path="admin/structure/webform/add">Add webform</a>');
    $this->assertRaw('<a href="' . $base_path . 'admin/structure/webform/add" class="button button-action" data-drupal-link-system-path="admin/structure/webform/add">Add webform</a>');

    /* UI description help */

    // Check moving #description to #help for webform admin routes.
    $this->drupalPostForm('/admin/structure/webform/config/advanced', ['ui[description_help]' => TRUE], t('Save configuration'));
    $this->assertRaw('<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;Display element description as help text (tooltip)&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;If checked, all element descriptions will be moved to help text (tooltip).&lt;/div&gt;"><span aria-hidden="true">?</span></span>');

    // Check moving #description to #help for webform admin routes.
    $this->drupalPostForm('/admin/structure/webform/config/advanced', ['ui[description_help]' => FALSE], t('Save configuration'));
    $this->assertNoRaw('<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;Display element description as help text (tooltip)&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;If checked, all element descriptions will be moved to help text (tooltip).&lt;/div&gt;"><span aria-hidden="true">?</span></span>');
  }

  /**
   * Sort a nested associative array by key.
   *
   * @param array $array
   *   A nested associative array.
   */
  protected function ksort(array &$array) {
    ksort($array);
    foreach ($array as &$value) {
      if (is_array($value)) {
        ksort($value);
      }
    }
  }

}
