<?php

namespace Drupal\webform\Tests;

use Drupal\Component\Serialization\Yaml;
use Drupal\webform\Utility\WebformYaml;

/**
 * Tests for webform entity.
 *
 * @group Webform
 */
class WebformAdminSettingsTest extends WebformTestBase {

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
  protected static $testWebforms = ['test_element', 'test_element_html_editor'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create users.
    $this->createUsers();
  }

  /**
   * Tests webform admin settings.
   */
  public function testAdminSettings() {
    global $base_path;

    $this->drupalLogin($this->adminWebformUser);

    /* Settings Webform */

    // Get 'webform.settings'.
    $original_data = \Drupal::configFactory()->getEditable('webform.settings')->getRawData();

    // Update 'settings.default_form_close_message'.
    $this->drupalPostForm('admin/structure/webform/settings', [], t('Save configuration'));
    \Drupal::configFactory()->reset('webform.settings');
    $updated_data = \Drupal::configFactory()->getEditable('webform.settings')->getRawData();

    // Check the updating 'Settings' via the UI did not lose or change any data.
    $this->assertEqual($updated_data, $original_data, 'Updated admin settings via the UI did not lose or change any data');

    // DEBUG:
    $this->verbose('<pre>' . WebformYaml::tidy(Yaml::encode($original_data)) . '</pre>');
    $this->verbose('<pre>' . WebformYaml::tidy(Yaml::encode($updated_data)) . '</pre>');

    /* Elements */

    // Check that description is 'after' the element.
    $this->drupalGet('webform/test_element');
    $this->assertPattern('#\{item title\}.+\{item markup\}.+\{item description\}#ms');

    // Set the default description display to 'before'.
    $this->drupalPostForm('admin/structure/webform/settings', ['elements[default_description_display]' => 'before'], t('Save configuration'));

    // Check that description is 'before' the element.
    $this->drupalGet('webform/test_element');
    $this->assertNoPattern('#\{item title\}.+\{item markup\}.+\{item description\}#ms');
    $this->assertPattern('#\{item title\}.+\{item description\}.+\{item markup\}#ms');

    /* UI disable dialog */

    // Check that dialogs are enabled.
    $this->drupalGet('admin/structure/webform');
    $this->assertRaw('<a href="' . $base_path . 'admin/structure/webform/add" class="button button-action button--primary button--small use-ajax" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:700}">Add webform</a>');

    // Disable dialogs.
    $this->drupalPostForm('admin/structure/webform/settings', ['ui[dialog_disabled]' => TRUE], t('Save configuration'));

    // Check that dialogs are disabled. (ie use-ajax is not included)
    $this->drupalGet('admin/structure/webform');
    $this->assertNoRaw('<a href="' . $base_path . 'admin/structure/webform/add" class="button button-action button--primary button--small use-ajax" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:700}">Add webform</a>');
    $this->assertRaw('<a href="' . $base_path . 'admin/structure/webform/add" class="button button-action button--primary button--small">Add webform</a>');

    /* UI disable html editor */

    // Check that HTML editor is enabled.
    $this->drupalGet('webform/test_element_html_editor');
    $this->assertRaw('<textarea data-drupal-selector="edit-webform-html-editor" id="edit-webform-html-editor" name="webform_html_editor" rows="5" cols="60" class="form-textarea resize-vertical">Hello &lt;b&gt;World!!!&lt;/b&gt;</textarea>');

    // Disable HTML editor.
    $this->drupalPostForm('admin/structure/webform/settings', ['ui[html_editor_disabled]' => TRUE], t('Save configuration'));

    // Check that HTML editor is removed and replaced by CodeMirror HTML editor.
    $this->drupalGet('webform/test_element_html_editor');
    $this->assertNoRaw('<textarea data-drupal-selector="edit-webform-html-editor" id="edit-webform-html-editor" name="webform_html_editor" rows="5" cols="60" class="form-textarea resize-vertical">Hello &lt;b&gt;World!!!&lt;/b&gt;</textarea>');
    $this->assertRaw('<textarea data-drupal-selector="edit-webform-html-editor" class="js-webform-codemirror webform-codemirror html form-textarea resize-vertical" data-webform-codemirror-mode="text/html" id="edit-webform-html-editor" name="webform_html_editor" rows="5" cols="60">Hello &lt;b&gt;World!!!&lt;/b&gt;</textarea>');

  }

}
