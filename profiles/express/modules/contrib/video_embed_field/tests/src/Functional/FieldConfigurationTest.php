<?php

namespace Drupal\Tests\video_embed_field\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Integration test for the field configuration form.
 *
 * @group video_embed_field
 */
class FieldConfigurationTest extends BrowserTestBase {

  use EntityDisplaySetupTrait;
  use AdminUserTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field_ui',
    'node',
    'video_embed_field',
  ];

  /**
   * Test the field configuration form.
   */
  public function testFieldConfiguration() {
    $this->drupalLogin($this->createAdminUser());
    $this->createContentType(['type' => 'page', 'name' => 'Page']);
    drupal_flush_all_caches();
    $this->drupalGet('admin/structure/types/manage/page/fields/add-field');
    $this->submitForm([
      'new_storage_type' => 'video_embed_field',
      'label' => 'Video Embed',
      'field_name' => 'video_embed',
    ], t('Save and continue'));
    $this->submitForm([], t('Save field settings'));
    $this->submitForm([
      'label' => 'Video Embed',
      'description' => 'Some help.',
      'required' => '1',
      'default_value_input[field_video_embed][0][value]' => 'http://example.com',
      'settings[allowed_providers][vimeo]' => 'vimeo',
      'settings[allowed_providers][youtube]' => 'youtube',
      'settings[allowed_providers][youtube_playlist]' => 'youtube_playlist',
    ], t('Save settings'));
    $this->assertSession()->pageTextContains('Could not find a video provider to handle the given URL.');
    $this->submitForm([
      'default_value_input[field_video_embed][0][value]' => 'https://www.youtube.com/watch?v=XgYu7-DQjDQ',
    ], t('Save settings'));
    $this->assertSession()->pageTextContains('Saved Video Embed configuration.');
  }

}
