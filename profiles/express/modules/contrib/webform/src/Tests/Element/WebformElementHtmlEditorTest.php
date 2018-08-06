<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform HTML editor element.
 *
 * @group Webform
 */
class WebformElementHtmlEditorTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['filter', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_html_editor'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create filters.
    $this->createFilters();
  }

  /**
   * Tests HTML Editor element.
   */
  public function testHtmlEditor() {
    $this->drupalLogin($this->rootUser);

    $this->drupalGet('webform/test_element_html_editor');

    // Check that HTML editor is enabled.
    $this->assertRaw('<textarea class="js-html-editor form-textarea resize-vertical" data-drupal-selector="edit-webform-html-editor-value" id="edit-webform-html-editor-value" name="webform_html_editor[value]" rows="5" cols="60">Hello &lt;b&gt;World!!!&lt;/b&gt;</textarea>');

    // Check that CodeMirror is displayed when #format: FALSE.
    $this->assertRaw('<textarea data-drupal-selector="edit-webform-html-editor-codemirror-value" class="js-webform-codemirror webform-codemirror html form-textarea resize-vertical" data-webform-codemirror-mode="text/html" id="edit-webform-html-editor-codemirror-value" name="webform_html_editor_codemirror[value]" rows="5" cols="60">Hello &lt;b&gt;World!!!&lt;/b&gt;</textarea>');

    // Disable HTML editor.
    $this->drupalPostForm('admin/structure/webform/config/elements', ['html_editor[disabled]' => TRUE], t('Save configuration'));

    // Check that HTML editor is removed and replaced by CodeMirror HTML editor.
    $this->drupalGet('webform/test_element_html_editor');
    $this->assertNoRaw('<textarea class="js-html-editor form-textarea resize-vertical" data-drupal-selector="edit-webform-html-editor-value" id="edit-webform-html-editor-value" name="webform_html_editor[value]" rows="5" cols="60">Hello &lt;b&gt;World!!!&lt;/b&gt;</textarea>');
    $this->assertRaw('<textarea data-drupal-selector="edit-webform-html-editor-value" class="js-webform-codemirror webform-codemirror html form-textarea resize-vertical" data-webform-codemirror-mode="text/html" id="edit-webform-html-editor-value" name="webform_html_editor[value]" rows="5" cols="60">Hello &lt;b&gt;World!!!&lt;/b&gt;</textarea>');

    // Enable HTML editor and text format.
    $edit = [
      'html_editor[disabled]' => FALSE,
      'html_editor[format]' => 'basic_html',
    ];
    $this->drupalPostForm('admin/structure/webform/config/elements', $edit, t('Save configuration'));

    // Check that Text format is disabled.
    $this->drupalGet('webform/test_element_html_editor');
    $this->assertNoRaw('<textarea class="js-html-editor form-textarea resize-vertical" data-drupal-selector="edit-webform-html-editor-value" id="edit-webform-html-editor-value" name="webform_html_editor[value]" rows="5" cols="60">Hello &lt;b&gt;World!!!&lt;/b&gt;</textarea>');
    $this->assertNoRaw('<textarea data-drupal-selector="edit-webform-html-editor-value" class="js-webform-codemirror webform-codemirror html form-textarea resize-vertical" data-webform-codemirror-mode="text/html" id="edit-webform-html-editor-value" name="webform_html_editor[value]" rows="5" cols="60">Hello &lt;b&gt;World!!!&lt;/b&gt;</textarea>');
    $this->assertRaw('<textarea data-drupal-selector="edit-webform-html-editor-value-value" id="edit-webform-html-editor-value-value" name="webform_html_editor[value][value]" rows="5" cols="60" class="form-textarea resize-vertical">Hello &lt;b&gt;World!!!&lt;/b&gt;</textarea>');
    $this->assertRaw('<h4 class="label">Basic HTML</h4>');

    // Disable text format.
    $this->drupalPostForm('admin/structure/webform/config/elements', ['html_editor[format]' => ''], t('Save configuration'));

    // Check that tidy removed <p> tags.
    $this->assertEqual(WebformHtmlEditor::checkMarkup('<p>Some text</p>', TRUE), 'Some text');
    $this->assertEqual(WebformHtmlEditor::checkMarkup('<p class="other">Some text</p>', TRUE), '<p class="other">Some text</p>');
    $this->assertEqual(WebformHtmlEditor::checkMarkup('<p>Some text</p><p>More text</p>', TRUE), '<p>Some text</p><p>More text</p>');

    // Disable HTML tidy.
    $this->drupalPostForm('admin/structure/webform/config/elements', ['html_editor[tidy]' => FALSE], t('Save configuration'));

    // Check that tidy is disabled.
    $this->assertEqual(WebformHtmlEditor::checkMarkup('<p>Some text</p>', TRUE), '<p>Some text</p>');
  }

}
