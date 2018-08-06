<?php

namespace Drupal\Tests\video_embed_field\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\video_embed_field\Plugin\Field\FieldFormatter\Thumbnail;

/**
 * Tests the field formatter configuration forms.
 *
 * @group video_embed_field
 */
class FormatterConfigurationTest extends BrowserTestBase {

  use AdminUserTrait;
  use EntityDisplaySetupTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'video_embed_field',
    'node',
    'field_ui',
    'colorbox',
  ];

  /**
   * The URL to the manage display interface.
   *
   * @var string
   */
  protected $manageDisplay;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->createAdminUser());
    $this->setupEntityDisplays();
    $this->manageDisplay = 'admin/structure/types/manage/test_content_type_name/display/teaser';
  }

  /**
   * Test the formatter configuration forms.
   */
  public function testVideoConfirmationForm() {
    // Test the settings form and summaries for the video formatter.
    $this->setFormatter('video_embed_field_video');
    $this->assertSession()->pageTextContains('Embedded Video (Responsive, autoplaying).');
    $this->updateFormatterSettings([
      'autoplay' => FALSE,
      'responsive' => FALSE,
      'width' => 100,
      'height' => 100,
    ]);
    $this->assertSession()->pageTextContains('Embedded Video (100x100).');

    // Test the image formatter.
    $this->setFormatter('video_embed_field_thumbnail');
    $this->assertSession()->pageTextContains('Video thumbnail (no image style).');
    $this->updateFormatterSettings([
      'image_style' => 'thumbnail',
      'link_image_to' => Thumbnail::LINK_CONTENT,
    ]);
    $this->assertSession()->pageTextContains('Video thumbnail (thumbnail, linked to content).');
    $this->updateFormatterSettings([
      'image_style' => 'medium',
      'link_image_to' => Thumbnail::LINK_PROVIDER,
    ]);
    $this->assertSession()->pageTextContains('Video thumbnail (medium, linked to provider).');

    $this->setFormatter('video_embed_field_colorbox');
    $this->assertSession()->pageTextContains('Thumbnail that launches a modal window.');
    $this->assertSession()->pageTextContains('Embedded Video (Responsive, autoplaying).');
    $this->assertSession()->pageTextContains('Video thumbnail (medium, linked to provider).');
    $this->updateFormatterSettings([
      'autoplay' => FALSE,
      'responsive' => FALSE,
      'width' => 100,
      'height' => 100,
      'image_style' => 'medium',
      'link_image_to' => Thumbnail::LINK_PROVIDER,
    ]);
    $this->assertSession()->pageTextContains('Thumbnail that launches a modal window.');
    $this->assertSession()->pageTextContains('Embedded Video (100x100).');
    $this->assertSession()->pageTextContains('Video thumbnail (medium, linked to provider).');
  }

  /**
   * Set the field formatter for the test field.
   *
   * @param string $formatter
   *   The field formatter ID to use.
   */
  protected function setFormatter($formatter) {
    $this->drupalGet($this->manageDisplay);
    $this->find('input[name="refresh_rows"]')->setValue($this->fieldName);
    $this->submitForm([
      'fields[' . $this->fieldName . '][type]' => $formatter,
      'fields[' . $this->fieldName . '][region]' => 'content',
    ], t('Refresh'));
    $this->submitForm([], t('Save'));
  }

  /**
   * Update the settings for the current formatter.
   *
   * @param array $settings
   *   The settings to update the foramtter with.
   */
  protected function updateFormatterSettings($settings) {
    $edit = [];
    foreach ($settings as $key => $value) {
      $edit["fields[{$this->fieldName}][settings_edit_form][settings][$key]"] = $value;
    }
    $this->drupalGet($this->manageDisplay);
    $this->find('input[name="' . $this->fieldName . '_settings_edit"]')->click();
    $this->submitForm($edit, $this->fieldName . '_plugin_settings_update');
    $this->submitForm([], t('Save'));
  }

  /**
   * Find an element based on a CSS selector.
   *
   * @param string $css_selector
   *   A css selector to find an element for.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   The found element or null.
   */
  protected function find($css_selector) {
    return $this->getSession()->getPage()->find('css', $css_selector);
  }

}
