<?php

/**
 * @file
 * Contains \Drupal\ckeditor\Tests\CKEditorLoadingTest.
 */

namespace Drupal\ckeditor\Tests;

use Drupal\editor\Entity\Editor;
use Drupal\simpletest\WebTestBase;
use Drupal\filter\Entity\FilterFormat;

/**
 * Tests loading of CKEditor.
 *
 * @group ckeditor
 */
class CKEditorLoadingTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('filter', 'editor', 'ckeditor', 'node');

  /**
   * An untrusted user with access to only the 'plain_text' format.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $untrustedUser;

  /**
   * A normal user with access to the 'plain_text' and 'filtered_html' formats.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $normalUser;

  protected function setUp() {
    parent::setUp();

    // Create text format, associate CKEditor.
    $filtered_html_format = FilterFormat::create(array(
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
      'filters' => array(),
    ));
    $filtered_html_format->save();
    $editor = Editor::create([
      'format' => 'filtered_html',
      'editor' => 'ckeditor',
    ]);
    $editor->save();

    // Create a second format without an associated editor so a drop down select
    // list is created when selecting formats.
    $full_html_format = FilterFormat::create(array(
      'format' => 'full_html',
      'name' => 'Full HTML',
      'weight' => 1,
      'filters' => array(),
    ));
    $full_html_format->save();

    // Create node type.
    $this->drupalCreateContentType(array(
      'type' => 'article',
      'name' => 'Article',
    ));

    $this->untrustedUser = $this->drupalCreateUser(array('create article content', 'edit any article content'));
    $this->normalUser = $this->drupalCreateUser(array('create article content', 'edit any article content', 'use text format filtered_html', 'use text format full_html'));
  }

  /**
   * Tests loading of CKEditor CSS, JS and JS settings.
   */
  function testLoading() {
    // The untrusted user:
    // - has access to 1 text format (plain_text);
    // - doesn't have access to the filtered_html text format, so: no text editor.
    $this->drupalLogin($this->untrustedUser);
    $this->drupalGet('node/add/article');
    list($settings, $editor_settings_present, $editor_js_present, $body, $format_selector) = $this->getThingsToCheck();
    $this->assertFalse($editor_settings_present, 'No Text Editor module settings.');
    $this->assertFalse($editor_js_present, 'No Text Editor JavaScript.');
    $this->assertTrue(count($body) === 1, 'A body field exists.');
    $this->assertTrue(count($format_selector) === 0, 'No text format selector exists on the page.');
    $hidden_input = $this->xpath('//input[@type="hidden" and contains(@class, "editor")]');
    $this->assertTrue(count($hidden_input) === 0, 'A single text format hidden input does not exist on the page.');
    $this->assertNoRaw(drupal_get_path('module', 'ckeditor') . '/js/ckeditor.js', 'CKEditor glue JS is absent.');

    // On pages where there would never be a text editor, CKEditor JS is absent.
    $this->drupalGet('user');
    $this->assertNoRaw(drupal_get_path('module', 'ckeditor') . '/js/ckeditor.js', 'CKEditor glue JS is absent.');

    // The normal user:
    // - has access to 2 text formats;
    // - does have access to the filtered_html text format, so: CKEditor.
    $this->drupalLogin($this->normalUser);
    $this->drupalGet('node/add/article');
    list($settings, $editor_settings_present, $editor_js_present, $body, $format_selector) = $this->getThingsToCheck();
    $ckeditor_plugin = $this->container->get('plugin.manager.editor')->createInstance('ckeditor');
    $editor = entity_load('editor', 'filtered_html');
    $expected = array('formats' => array('filtered_html' => array(
      'format' => 'filtered_html',
      'editor' => 'ckeditor',
      'editorSettings' => $this->castSafeStrings($ckeditor_plugin->getJSSettings($editor)),
      'editorSupportsContentFiltering' => TRUE,
      'isXssSafe' => FALSE,
    )));
    $this->assertTrue($editor_settings_present, "Text Editor module's JavaScript settings are on the page.");
    $this->assertIdentical($expected, $this->castSafeStrings($settings['editor']), "Text Editor module's JavaScript settings on the page are correct.");
    $this->assertTrue($editor_js_present, 'Text Editor JavaScript is present.');
    $this->assertTrue(count($body) === 1, 'A body field exists.');
    $this->assertTrue(count($format_selector) === 1, 'A single text format selector exists on the page.');
    $specific_format_selector = $this->xpath('//select[contains(@class, "filter-list") and @data-editor-for="edit-body-0-value"]');
    $this->assertTrue(count($specific_format_selector) === 1, 'A single text format selector exists on the page and has a "data-editor-for" attribute with the correct value.');
    $this->assertTrue(in_array('ckeditor/drupal.ckeditor', explode(',', $settings['ajaxPageState']['libraries'])), 'CKEditor glue library is present.');

    // Enable the ckeditor_test module, customize configuration. In this case,
    // there is additional CSS and JS to be loaded.
    // NOTE: the tests in CKEditorTest already ensure that changing the
    // configuration also results in modified CKEditor configuration, so we
    // don't test that here.
    \Drupal::service('module_installer')->install(array('ckeditor_test'));
    $this->container->get('plugin.manager.ckeditor.plugin')->clearCachedDefinitions();
    $editor_settings = $editor->getSettings();
    $editor_settings['toolbar']['rows'][0][0]['items'][] = 'Llama';
    $editor->setSettings($editor_settings);
    $editor->save();
    $this->drupalGet('node/add/article');
    list($settings, $editor_settings_present, $editor_js_present, $body, $format_selector) = $this->getThingsToCheck();
    $expected = array(
      'formats' => array(
        'filtered_html' => array(
          'format' => 'filtered_html',
          'editor' => 'ckeditor',
          'editorSettings' => $this->castSafeStrings($ckeditor_plugin->getJSSettings($editor)),
          'editorSupportsContentFiltering' => TRUE,
          'isXssSafe' => FALSE,
    )));
    $this->assertTrue($editor_settings_present, "Text Editor module's JavaScript settings are on the page.");
    $this->assertIdentical($expected, $this->castSafeStrings($settings['editor']), "Text Editor module's JavaScript settings on the page are correct.");
    $this->assertTrue($editor_js_present, 'Text Editor JavaScript is present.');
    $this->assertTrue(in_array('ckeditor/drupal.ckeditor', explode(',', $settings['ajaxPageState']['libraries'])), 'CKEditor glue library is present.');

    // Assert that CKEditor uses Drupal's cache-busting query string by
    // comparing the setting sent with the page with the current query string.
    $settings = $this->getDrupalSettings();
    $expected = $settings['ckeditor']['timestamp'];
    $this->assertIdentical($expected, \Drupal::state()->get('system.css_js_query_string'), "CKEditor scripts cache-busting string is correct before flushing all caches.");
    // Flush all caches then make sure that $settings['ckeditor']['timestamp']
    // still matches.
    drupal_flush_all_caches();
    $this->assertIdentical($expected, \Drupal::state()->get('system.css_js_query_string'), "CKEditor scripts cache-busting string is correct after flushing all caches.");
  }

  protected function getThingsToCheck() {
    $settings = $this->getDrupalSettings();
    return array(
      // JavaScript settings.
      $settings,
      // Editor.module's JS settings present.
      isset($settings['editor']),
      // Editor.module's JS present. Note: ckeditor/drupal.ckeditor depends on
      // editor/drupal.editor, hence presence of the former implies presence of
      // the latter.
      isset($settings['ajaxPageState']['libraries']) && in_array('ckeditor/drupal.ckeditor', explode(',', $settings['ajaxPageState']['libraries'])),
      // Body field.
      $this->xpath('//textarea[@id="edit-body-0-value"]'),
      // Format selector.
      $this->xpath('//select[contains(@class, "filter-list")]'),
    );
  }
}
