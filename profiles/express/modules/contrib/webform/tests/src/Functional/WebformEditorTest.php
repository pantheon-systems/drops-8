<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\file\Entity\File;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform editor.
 *
 * @group Webform
 */
class WebformEditorTest extends WebformBrowserTestBase {

  use TestFileCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['file', 'webform'];

  /**
   * File usage manager.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->fileUsage = $this->container->get('file.usage');
  }

  /**
   * Tests webform entity settings files.
   */
  public function testWebformSettingsFiles() {
    $this->drupalLogin($this->rootUser);

    // Create three test images.
    /** @var \Drupal\file\FileInterface[] $images */
    $images = $this->getTestFiles('image');
    $images = array_slice($images, 0, 5);
    foreach ($images as $index => $image_file) {
      $images[$index] = File::create((array) $image_file);
      $images[$index]->save();
    }

    // Check that all images are temporary.
    $this->assertTrue($images[0]->isTemporary());
    $this->assertTrue($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());

    // Upload the first image.
    $edit = [
      'description[value]' => '<img data-entity-type="file" data-entity-uuid="' . $images[0]->uuid() . '"/>',
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/contact/settings', $edit, t('Save'));
    $this->reloadImages($images);

    // Check that first image is not temporary.
    $this->assertFalse($images[0]->isTemporary());
    $this->assertTrue($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());

    // Check create first image file usage.
    $this->assertIdentical(['editor' => ['webform' => ['contact' => '1']]], $this->fileUsage->listUsage($images[0]), 'The file has 1 usage.');

    // Upload the second image.
    $edit = [
      'description[value]' => '<img data-entity-type="file" data-entity-uuid="' . $images[0]->uuid() . '"/><img data-entity-type="file" data-entity-uuid="' . $images[1]->uuid() . '"/>',
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/contact/settings', $edit, t('Save'));
    $this->reloadImages($images);

    // Check that first and second image are not temporary.
    $this->assertFalse($images[0]->isTemporary());
    $this->assertFalse($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());

    // Check first and second image file usage.
    $this->assertIdentical(['editor' => ['webform' => ['contact' => '1']]], $this->fileUsage->listUsage($images[0]), 'The file has 1 usage.');
    $this->assertIdentical(['editor' => ['webform' => ['contact' => '1']]], $this->fileUsage->listUsage($images[1]), 'The file has 1 usage.');

    // Remove the first image.
    $edit = [
      'description[value]' => '<img data-entity-type="file" data-entity-uuid="' . $images[1]->uuid() . '"/>',
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/contact/settings', $edit, t('Save'));
    $this->reloadImages($images);

    // Check that first is temporary and second image is not temporary.
    $this->assertTrue($images[0]->isTemporary());
    $this->assertFalse($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());

    // Check first and second image file usage.
    $this->assertIdentical([], $this->fileUsage->listUsage($images[0]), 'The file has 0 usage.');
    $this->assertIdentical(['editor' => ['webform' => ['contact' => '1']]], $this->fileUsage->listUsage($images[1]), 'The file has 1 usage.');

    // Set all files back to temporary.
    $edit = [
      'description[value]' => '',
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/contact/settings', $edit, t('Save'));
    $this->reloadImages($images);

    // Check that first and second image are temporary.
    $this->assertTrue($images[0]->isTemporary());
    $this->assertTrue($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());

    // Stop marking unused files as temporary.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('html_editor.make_unused_managed_files_temporary', FALSE)
      ->save();
    $this->assertTrue($images[0]->isTemporary());

    // Check uploaded file is NOT temporary.
    $this->assertTrue($images[0]->isTemporary());
    $edit = [
      'description[value]' => '<img data-entity-type="file" data-entity-uuid="' . $images[0]->uuid() . '"/>',
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/contact/settings', $edit, t('Save'));
    $this->reloadImages($images);
    $this->assertFalse($images[0]->isTemporary());

    // Check unused file is NOT temporary.
    $edit = [
      'description[value]' => '',
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/contact/settings', $edit, t('Save'));
    $this->reloadImages($images);
    $this->assertFalse($images[0]->isTemporary());

    // Start marking unused files as temporary.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('html_editor.make_unused_managed_files_temporary', TRUE)
      ->save();

    $edit = [
      'description[value]' => '<img data-entity-type="file" data-entity-uuid="' . $images[0]->uuid() . '"/>',
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/contact/settings', $edit, t('Save'));
    $this->reloadImages($images);

    // Check that upload file is not temporary.
    $this->assertFalse($images[0]->isTemporary());

    // Delete the webform.
    Webform::load('contact')->delete();
    $this->reloadImages($images);

    // Check that file is temporary after the webform is deleted.
    $this->assertTrue($images[0]->isTemporary());
  }

  /**
   * Tests webform configuration files.
   */
  public function testWebformConfigurationFiles() {
    $this->drupalLogin($this->rootUser);

    // Create three test images.
    /** @var \Drupal\file\FileInterface[] $images */
    $images = $this->getTestFiles('image');
    $images = array_slice($images, 0, 5);
    foreach ($images as $index => $image_file) {
      $images[$index] = File::create((array) $image_file);
      $images[$index]->save();
    }

    // Check that all images are temporary.
    $this->assertTrue($images[0]->isTemporary());
    $this->assertTrue($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());

    // Upload the first image.
    $edit = [
      'form_settings[default_form_open_message][value]' => '<img data-entity-type="file" data-entity-uuid="' . $images[0]->uuid() . '"/>',
    ];
    $this->drupalPostForm('/admin/structure/webform/config', $edit, t('Save configuration'));
    $this->reloadImages($images);

    // Check that first image is not temporary.
    $this->assertFalse($images[0]->isTemporary());
    $this->assertTrue($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());

    // Check create first image file usage.
    $this->assertIdentical(['editor' => ['config' => ['webform.settings' => '1']]], $this->fileUsage->listUsage($images[0]), 'The file has 1 usage.');

    // Upload the second image.
    $edit = [
      'form_settings[default_form_open_message][value]' => '<img data-entity-type="file" data-entity-uuid="' . $images[0]->uuid() . '"/><img data-entity-type="file" data-entity-uuid="' . $images[1]->uuid() . '"/>',
    ];
    $this->drupalPostForm('/admin/structure/webform/config', $edit, t('Save configuration'));
    $this->reloadImages($images);

    // Check that first and second image are not temporary.
    $this->assertFalse($images[0]->isTemporary());
    $this->assertFalse($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());

    // Check first and second image file usage.
    $this->assertIdentical(['editor' => ['config' => ['webform.settings' => '1']]], $this->fileUsage->listUsage($images[0]), 'The file has 1 usage.');
    $this->assertIdentical(['editor' => ['config' => ['webform.settings' => '1']]], $this->fileUsage->listUsage($images[1]), 'The file has 1 usage.');

    // Remove the first image.
    $edit = [
      'form_settings[default_form_open_message][value]' => '<img data-entity-type="file" data-entity-uuid="' . $images[1]->uuid() . '"/>',
    ];
    $this->drupalPostForm('/admin/structure/webform/config', $edit, t('Save configuration'));
    $this->reloadImages($images);

    // Check that first is temporary and second image is not temporary.
    $this->assertTrue($images[0]->isTemporary());
    $this->assertFalse($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());

    // Check first and second image file usage.
    $this->assertIdentical([], $this->fileUsage->listUsage($images[0]), 'The file has 0 usage.');
    $this->assertIdentical(['editor' => ['config' => ['webform.settings' => '1']]], $this->fileUsage->listUsage($images[1]), 'The file has 1 usage.');

    // Simulate deleting webform.settings.yml during webform uninstall.
    // @see webform_uninstall()
    $config = \Drupal::configFactory()->get('webform.settings');
    _webform_config_delete($config);
    $this->reloadImages($images);

    // Check that first and second image are temporary.
    $this->assertTrue($images[0]->isTemporary());
    $this->assertTrue($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());
  }

  /****************************************************************************/
  // Helper functions.
  /****************************************************************************/

  /**
   * Reload images.
   *
   * @param array $images
   *   An array of image files.
   */
  protected function reloadImages(array &$images) {
    \Drupal::entityTypeManager()->getStorage('file')->resetCache();
    foreach ($images as $index => $image) {
      $images[$index] = File::load($image->id());
    }
  }

}
