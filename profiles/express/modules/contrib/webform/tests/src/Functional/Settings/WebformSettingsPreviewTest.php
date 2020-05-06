<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\webform\Entity\Webform;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform submission form preview.
 *
 * @group Webform
 */
class WebformSettingsPreviewTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_preview'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Exclude Progress tracker so that the default progress bar is displayed.
    // The default progress bar is most likely never going to change.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('libraries.excluded_libraries', ['progress-tracker'])
      ->save();
  }

  /**
   * Tests webform webform submission form preview.
   */
  public function testPreview() {
    $this->drupalLogin($this->rootUser);

    $webform_preview = Webform::load('test_form_preview');

    // Check webform with optional preview.
    $this->drupalGet('/webform/test_form_preview');
    $this->assertFieldByName('op', 'Submit');
    $this->assertFieldByName('op', 'Preview');

    // Check default preview with values.
    $this->drupalPostForm('/webform/test_form_preview', ['name' => 'test', 'email' => 'example@example.com', 'checkbox' => TRUE], t('Preview'));

    $this->assertRaw('<h1 class="page-title">Test: Webform: Preview: Preview</h1>');

    $this->assertRaw('<b>Preview</b></li>');

    $this->assertRaw('Please review your submission. Your submission is not complete until you press the "Submit" button!');

    $this->assertFieldByName('op', 'Submit');
    $this->assertFieldByName('op', '< Previous');

    $this->assertRaw('<div class="webform-preview js-form-wrapper form-wrapper" data-drupal-selector="edit-preview" id="edit-preview">');
    $this->assertRaw('<div data-drupal-selector="edit-submission" class="webform-submission-data webform-submission-data--webform-test-form-preview webform-submission-data--view-mode-preview">');
    $this->assertRaw('<fieldset class="format-attributes-class webform-container webform-container-type-fieldset js-form-item form-item js-form-wrapper form-wrapper" id="test_form_preview--fieldset">');
    $this->assertRaw('<div class="format-attributes-class webform-element webform-element-type-textfield js-form-item form-item js-form-type-item form-type-item js-form-item-name form-item-name" id="test_form_preview--name">');
    $this->assertRaw('<label>Name</label>' . PHP_EOL . '        test');

    $this->assertRaw('<section class="format-attributes-class js-form-item form-item js-form-wrapper form-wrapper webform-section" id="test_form_preview--container">');
    $this->assertRaw('<div class="format-attributes-class webform-element webform-element-type-email js-form-item form-item js-form-type-item form-type-item js-form-item-email form-item-email" id="test_form_preview--email">');
    $this->assertRaw('<label>Email</label>' . PHP_EOL . '        <a href="mailto:example@example.com">example@example.com</a>');

    $this->assertRaw('<div class="format-attributes-class webform-element webform-element-type-checkbox js-form-item form-item js-form-type-item form-type-item js-form-item-checkbox form-item-checkbox" id="test_form_preview--checkbox">');
    $this->assertRaw('<section class="format-attributes-class js-form-item form-item js-form-wrapper form-wrapper webform-section" id="test_form_preview--section">');
    $this->assertRaw('<label>Checkbox</label>' . PHP_EOL . '        Yes');
    $this->assertRaw('<div class="webform-preview js-form-wrapper form-wrapper" data-drupal-selector="edit-preview" id="edit-preview">');

    // Check default preview without values.
    $this->drupalPostForm('/webform/test_form_preview', [], t('Preview'));
    $this->assertNoRaw('<label>Name</label>');
    $this->assertNoRaw('<label>Email</label>');
    $this->assertNoRaw('<label>Checkbox</label>');

    // Check submission view without values.
    $sid = $this->postSubmission($webform_preview);
    $this->drupalGet("admin/structure/webform/manage/test_form_preview/submission/$sid");
    $this->assertNoRaw('<label>Name</label>');
    $this->assertNoRaw('<label>Email</label>');
    $this->assertNoRaw('<label>Checkbox</label>');

    // Check submission table without values.
    $this->drupalGet("admin/structure/webform/manage/test_form_preview/submission/$sid/table");
    $this->assertNoRaw('<th>Name</th>');
    $this->assertNoRaw('<th>Email</th>');
    $this->assertNoRaw('<th>Checkbox</th>');
    $this->assertNoRaw('<td>No</td>');

    // Clear default preview message.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_preview_message', '')
      ->save();

    // Check blank preview message is not displayed.
    $this->drupalPostForm('/webform/test_form_preview', ['name' => 'test', 'email' => 'example@example.com'], t('Preview'));
    $this->assertNoRaw('Please review your submission. Your submission is not complete until you press the "Submit" button!');

    // Set preview and submission to include empty.
    $webform_preview->setSetting('preview_exclude_empty', FALSE);
    $webform_preview->setSetting('preview_exclude_empty_checkbox', FALSE);
    $webform_preview->setSetting('submission_exclude_empty', FALSE);
    $webform_preview->setSetting('submission_exclude_empty_checkbox', FALSE);
    $webform_preview->save();

    // Check empty elements are included in preview.
    $this->drupalPostForm('/webform/test_form_preview', ['name' => '', 'email' => '', 'checkbox' => FALSE], t('Preview'));
    $this->assertRaw('<label>Name</label>' . PHP_EOL . '        {Empty}');
    $this->assertRaw('<div class="format-attributes-class webform-element webform-element-type-email js-form-item form-item js-form-type-item form-type-item js-form-item-email form-item-email" id="test_form_preview--email">');
    $this->assertRaw('<label>Email</label>' . PHP_EOL . '        {Empty}');
    $this->assertRaw('<label>Checkbox</label>' . PHP_EOL . '        No');

    // Check empty elements are included in submission view.
    $sid = $this->postSubmission($webform_preview);
    $this->drupalGet("admin/structure/webform/manage/test_form_preview/submission/$sid");
    $this->assertRaw('<label>Name</label>');
    $this->assertRaw('<label>Email</label>');
    $this->assertRaw('<label>Checkbox</label>');

    // Check submission table without values.
    $this->drupalGet("admin/structure/webform/manage/test_form_preview/submission/$sid/table");
    $this->assertRaw('<th>Name</th>');
    $this->assertRaw('<th>Email</th>');
    $this->assertRaw('<th>Checkbox</th>');
    $this->assertRaw('<td>No</td>');

    // Add special character to title.
    $webform_preview->set('title', "This has special characters. '<>\"&");
    $webform_preview->save();

    // Check special characters in form page title.
    $this->drupalGet('/webform/test_form_preview');
    $this->assertRaw('<title>This has special characters. \'"& | Drupal</title>');
    $this->assertRaw('<h1 class="page-title">This has special characters. &#039;&lt;&gt;&quot;&amp;</h1>');

    // Check special characters in preview page title.
    $this->drupalPostForm('/webform/test_form_preview', ['name' => 'test'], t('Preview'));
    $this->assertRaw('<title>This has special characters. \'"&: Preview | Drupal</title>');
    $this->assertRaw('<h1 class="page-title">This has special characters. &#039;&lt;&gt;&quot;&amp;: Preview</h1>');

    // Check required preview with custom settings.
    $webform_preview->setSettings([
      'preview' => DRUPAL_REQUIRED,
      'preview_label' => '{Label}',
      'preview_title' => '{Title}',
      'preview_message' => '{Message}',
      'preview_attributes' => ['class' => ['preview-custom']],
      'preview_excluded_elements' => ['email' => 'email'],
    ]);

    // Add 'webform_actions' element.
    $webform_preview->setElementProperties('actions', [
      '#type' => 'webform_actions',
      '#preview_next__label' => '{Preview}',
      '#preview_prev__label' => '{Back}',
    ]);
    $webform_preview->save();

    // Check custom preview.
    $this->drupalPostForm('/webform/test_form_preview', ['name' => 'test'], t('{Preview}'));
    $this->assertRaw('<h1 class="page-title">{Title}</h1>');
    $this->assertRaw('<b>{Label}</b></li>');
    $this->assertRaw('{Message}');
    $this->assertFieldByName('op', 'Submit');
    $this->assertFieldByName('op', '{Back}');
    $this->assertRaw('<label>Name</label>' . PHP_EOL . '        test');
    $this->assertNoRaw('<label>Email</label>');
    $this->assertRaw('<div class="preview-custom webform-preview js-form-wrapper form-wrapper" data-drupal-selector="edit-preview" id="edit-preview">');

    $this->drupalGet('/webform/test_form_preview');
    $this->assertNoFieldByName('op', 'Submit');
    $this->assertFieldByName('op', '{Preview}');

    // Check empty element is excluded from preview.
    $this->drupalPostForm('/webform/test_form_preview', ['name' => 'test', 'email' => ''], t('{Preview}'));
    $this->assertRaw('<label>Name</label>' . PHP_EOL . '        test');
    $this->assertNoRaw('<label>Email</label>');
  }

}
