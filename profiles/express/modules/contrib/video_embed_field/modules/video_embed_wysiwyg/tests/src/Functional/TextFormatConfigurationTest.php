<?php

namespace Drupal\Tests\video_embed_wysiwyg\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\video_embed_field\Functional\AdminUserTrait;

/**
 * Test the format configuration form.
 *
 * @group video_embed_wysiwyg
 */
class TextFormatConfigurationTest extends BrowserTestBase {

  use AdminUserTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'video_embed_field',
    'video_embed_wysiwyg',
    'editor',
    'ckeditor',
    'field_ui',
    'node',
    'image',
  ];

  /**
   * The URL for the filter format.
   *
   * @var string
   */
  protected $formatUrl = 'admin/config/content/formats/manage/plain_text';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->createAdminUser());
    $this->drupalGet($this->formatUrl);

    // Setup the filter to have an editor.
    $this->getSession()->getPage()->find('css', '[name="editor[editor]"]')->setValue('ckeditor');
    $this->getSession()->getPage()->find('css', 'input[name="editor_configure"]')->click();
    $this->submitForm([], t('Save configuration'));
  }

  /**
   * Test both the input filter and button need to be enabled together.
   */
  public function testFormatConfiguration() {
    // Save the settings with the filter enabled, but with no button.
    $this->drupalGet($this->formatUrl);
    $this->submitForm([
      'filters[video_embed_wysiwyg][status]' => TRUE,
      'editor[settings][toolbar][button_groups]' => '[]',
    ], t('Save configuration'));
    $this->assertSession()->pageTextContains('To embed videos, make sure you have enabled the "Video Embed WYSIWYG" filter and dragged the video icon into the WYSIWYG toolbar.');

    $this->drupalGet($this->formatUrl);
    $this->submitForm([
      'filters[video_embed_wysiwyg][status]' => FALSE,
      'editor[settings][toolbar][button_groups]' => '[[{"name":"Group","items":["video_embed"]}]]',
    ], t('Save configuration'));
    $this->assertSession()->pageTextContains('To embed videos, make sure you have enabled the "Video Embed WYSIWYG" filter and dragged the video icon into the WYSIWYG toolbar.');

    $this->drupalGet($this->formatUrl);
    $this->submitForm([
      'filters[video_embed_wysiwyg][status]' => TRUE,
      'editor[settings][toolbar][button_groups]' => '[[{"name":"Group","items":["video_embed"]}]]',
    ], t('Save configuration'));
    $this->assertSession()->pageTextContains('The text format Plain text has been updated.');

    // Test the messages aren't triggered if they are in the second row.
    $this->drupalGet($this->formatUrl);
    $this->submitForm([
      'filters[video_embed_wysiwyg][status]' => TRUE,
      'editor[settings][toolbar][button_groups]' => '[[{"name":"Foo","items":["NumberedList"]}],[{"name":"Bar","items":["video_embed"]}]]',
    ], t('Save configuration'));
    $this->assertSession()->pageTextContains('The text format Plain text has been updated.');
  }

  /**
   * Test the URL filter weight is in the correct order.
   */
  public function testUrlWeightOrder() {
    $this->drupalGet($this->formatUrl);
    $this->submitForm([
      // Enable the URL filter and the WYSIWYG embed.
      'filters[filter_url][status]' => TRUE,
      'filters[filter_html][status]' => FALSE,
      'filters[video_embed_wysiwyg][status]' => TRUE,
      'editor[settings][toolbar][button_groups]' => '[[{"name":"Group","items":["video_embed"]}]]',
      // Setup the weights so the URL filter runs first.
      'filters[video_embed_wysiwyg][weight]' => '10',
      'filters[filter_url][weight]' => '-10',
    ], 'Save configuration');
    $this->assertSession()->pageTextContains('The "Video Embed WYSIWYG" filter must run before the "Convert URLs into links" filter to function correctly.');

    // Submit the form with the weights reversed.
    $this->submitForm([
      'filters[video_embed_wysiwyg][weight]' => '-10',
      'filters[filter_url][weight]' => '10',
    ], 'Save configuration');
    $this->assertSession()->pageTextContains('The text format Plain text has been updated.');
  }

  /**
   * Test the URL filter weight is in the correct order.
   */
  public function testHtmlFilterWeightOrder() {
    $this->drupalGet($this->formatUrl);
    $this->submitForm([
      // Enable the URL filter and the WYSIWYG embed.
      'filters[filter_html][status]' => TRUE,
      'filters[filter_url][status]' => FALSE,
      'filters[video_embed_wysiwyg][status]' => TRUE,
      'editor[settings][toolbar][button_groups]' => '[[{"name":"Group","items":["video_embed"]}]]',
      // Run WYSWIYG first then the HTML filter.
      'filters[video_embed_wysiwyg][weight]' => '-10',
      'filters[filter_html][weight]' => '10',
    ], 'Save configuration');
    $this->assertSession()->pageTextContains('The "Video Embed WYSIWYG" filter must run after the "Limit allowed HTML tags" filter to function correctly.');

    // Submit the form with the weights reversed.
    $this->submitForm([
      'filters[video_embed_wysiwyg][weight]' => '10',
      'filters[filter_html][weight]' => '-10',
    ], 'Save configuration');
    $this->assertSession()->pageTextContains('The text format Plain text has been updated.');
  }

  /**
   * Test the dialog defaults can be set and work correctly.
   */
  public function testDialogDefaultValues() {
    $this->drupalGet($this->formatUrl);

    // Assert all the form fields that appear on the modal, appear as
    // configurable defaults.
    $this->assertSession()->pageTextContains('Autoplay');
    $this->assertSession()->pageTextContains('Responsive Video');
    $this->assertSession()->pageTextContains('Width');
    $this->assertSession()->pageTextContains('Height');

    $this->submitForm([
      'filters[video_embed_wysiwyg][status]' => TRUE,
      'editor[settings][toolbar][button_groups]' => '[[{"name":"Group","items":["video_embed"]}]]',
      'editor[settings][plugins][video_embed][defaults][children][width]' => '123',
      'editor[settings][plugins][video_embed][defaults][children][height]' => '456',
      'editor[settings][plugins][video_embed][defaults][children][responsive]' => FALSE,
      'editor[settings][plugins][video_embed][defaults][children][autoplay]' => FALSE,
    ], t('Save configuration'));

    // Ensure the configured defaults show up on the modal window.
    $this->drupalGet('video-embed-wysiwyg/dialog/plain_text');
    $this->assertSession()->fieldValueEquals('width', '123');
    $this->assertSession()->fieldValueEquals('height', '456');
    $this->assertSession()->fieldValueEquals('autoplay', FALSE);
    $this->assertSession()->fieldValueEquals('responsive', FALSE);
  }

}
