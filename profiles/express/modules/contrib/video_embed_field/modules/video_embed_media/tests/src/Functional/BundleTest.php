<?php

namespace Drupal\Tests\video_embed_media\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\video_embed_field\Functional\AdminUserTrait;

/**
 * Test the video_embed_field media integration.
 *
 * @group video_embed_media
 */
class BundleTest extends BrowserTestBase {

  use AdminUserTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'video_embed_field',
    'video_embed_media',
    'media_entity',
    'field_ui',
    'node',
    'image',
  ];

  /**
   * Test the dialog form.
   */
  public function testMediaBundleCreation() {
    $this->drupalLogin($this->createAdminUser());

    // Create a new media bundle.
    $this->drupalGet('admin/structure/media/add');
    $this->submitForm([
      'label' => 'Video Bundle',
      'id' => 'video_bundle',
      'type' => 'video_embed_field',
    ], t('Save media bundle'));
    $this->assertSession()->pageTextContains('The media bundle Video Bundle has been added.');

    // Ensure the video field is added to the media entity.
    $this->drupalGet('admin/structure/media/manage/video_bundle/fields');
    $this->assertSession()->pageTextContains('field_media_video_embed_field');
    $this->assertSession()->pageTextContains('Video URL');

    // Add a media entity with the new field.
    $this->drupalGet('media/add/video_bundle');
    $this->submitForm([
      'name[0][value]' => 'Drupal video!',
      'field_media_video_embed_field[0][value]' => 'https://www.youtube.com/watch?v=XgYu7-DQjDQ',
    ], 'Save');
    // We should see the video thumbnail on the media page.
    $this->assertContains('video_thumbnails/XgYu7-DQjDQ.jpg', $this->getSession()->getPage()->getHtml());

    // Add another field and change the configured media field.
    $this->drupalGet('admin/structure/media/manage/video_bundle/fields/add-field');
    $this->submitForm([
      'new_storage_type' => 'video_embed_field',
      'label' => 'New Video Field',
      'field_name' => 'new_video_field',
    ], 'Save and continue');
    $this->submitForm([], t('Save field settings'));
    $this->submitForm([], t('Save settings'));

    // Update video source field.
    $this->drupalGet('admin/structure/media/manage/video_bundle');
    $this->submitForm([
      'type_configuration[video_embed_field][source_field]' => 'field_new_video_field',
    ], t('Save media bundle'));

    // Create a video, populating both video URL fields.
    $this->drupalGet('media/add/video_bundle');
    $this->submitForm([
      'name[0][value]' => 'Another Video!',
      'field_media_video_embed_field[0][value]' => 'https://www.youtube.com/watch?v=XgYu7-DQjDQ',
      'field_new_video_field[0][value]' => 'https://www.youtube.com/watch?v=gnERPdAiuSo',
    ], t('Save'));

    // We should see the newly configured video thumbnail, but not the original.
    $this->assertContains('video_thumbnails/gnERPdAiuSo.jpg', $this->getSession()->getPage()->getHtml());
    $this->assertNotContains('video_thumbnails/XgYu7-DQjDQ.jpg', $this->getSession()->getPage()->getHtml());
  }

}
