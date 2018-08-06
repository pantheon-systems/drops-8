<?php

namespace Drupal\Tests\video_embed_media\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\video_embed_field\Functional\AdminUserTrait;

/**
 * Test the upgrade path from media_entity_embedded_video.
 *
 * @group video_embed_media
 */
class UpgradePathTest extends BrowserTestBase {

  use AdminUserTrait;

  /**
   * Disable strict checking because we are installing MEEV.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'video_embed_field',
    'media_entity_embeddable_video',
    'media_entity',
    'field_ui',
    'node',
    'image',
    'text',
  ];

  /**
   * Test the upgrade path.
   */
  public function testMediaBundleCreation() {
    $this->drupalLogin($this->createAdminUser());

    // Create a media_entity_embeddable_video bundle and field.
    $this->drupalGet('admin/structure/media/add');
    $this->submitForm([
      'label' => 'embeddable Video Bundle',
      'id' => 'embeddable_bundle',
      'type' => 'embeddable_video',
    ], 'Save media bundle');
    $this->assertSession()->pageTextContains('The media bundle embeddable Video Bundle has been added.');
    $this->drupalGet('admin/structure/media/manage/embeddable_bundle/fields/add-field');
    $this->submitForm([
      'new_storage_type' => 'string',
      'label' => 'Video Text Field',
      'field_name' => 'video_text_field',
    ], t('Save and continue'));
    $this->submitForm([], t('Save field settings'));
    $this->submitForm([], t('Save settings'));
    $this->drupalGet('admin/structure/media/manage/embeddable_bundle');
    $this->submitForm(['type_configuration[embeddable_video][source_field]' => 'field_video_text_field'], t('Save media bundle'));
    $this->drupalGet('media/add/embeddable_bundle');
    $this->submitForm([
      'field_video_text_field[0][value]' => 'https://www.youtube.com/watch?v=gnERPdAiuSo',
      'name[0][value]' => 'Test Media Entity',
    ], t('Save'));

    // Install video_embed_field.
    $this->container->get('module_installer')->install(['video_embed_media'], TRUE);

    $this->assertUpgradeComplete();

    // Uninstall the module and ensure everything is still okay.
    $this->drupalGet('admin/modules/uninstall');
    $this->submitForm([
      'uninstall[media_entity_embeddable_video]' => TRUE,
    ], t('Uninstall'));
    $this->submitForm([], 'Uninstall');

    $this->assertUpgradeComplete();
  }

  /**
   * Assert the upgrade was successful.
   */
  protected function assertUpgradeComplete() {
    // Ensure the new type is selected.
    $this->drupalGet('admin/structure/media/manage/embeddable_bundle');
    $this->assertTrue(!empty($this->getSession()->getPage()->find('xpath', '//option[@value="video_embed_field" and @selected="selected"]')), 'The media type was updated.');
    // Ensure the media entity has updated values.
    $this->drupalGet('media/1/edit');
    $this->assertEquals($this->getSession()->getPage()->find('css', 'input[name="field_video_text_field[0][value]"]')->getValue(), 'https://www.youtube.com/watch?v=gnERPdAiuSo', 'Field values were copied.');
  }

}
