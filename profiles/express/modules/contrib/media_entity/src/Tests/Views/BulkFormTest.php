<?php

namespace Drupal\media_entity\Tests\Views;

use Drupal\media_entity\Tests\MediaTestTrait;
use Drupal\media_entity\Entity\Media;
use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests a media bulk form.
 *
 * @group media_entity
 */
class BulkFormTest extends ViewTestBase {

  use MediaTestTrait;

  /**
   * Modules to be enabled.
   *
   * @var array
   */
  public static $modules = ['media_entity_test_views'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_media_entity_bulk_form'];

  /**
   * The test media bundle.
   *
   * @var \Drupal\media_entity\MediaBundleInterface
   */
  protected $testBundle;

  /**
   * The test media entities.
   *
   * @var \Drupal\media_entity\MediaInterface[]
   */
  protected $mediaEntities;

  /**
   * The test user.
   *
   * @var \Drupal\User\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    if ($import_test_views) {
      ViewTestData::createTestViews(get_class($this), ['media_entity_test_views']);
    }

    $this->testBundle = $this->drupalCreateMediaBundle();

    // Create some test media entities.
    $this->mediaEntities = [];
    for ($i = 1; $i <= 5; $i++) {
      $media = Media::create([
        'bundle' => $this->testBundle->id(),
        'name' => $this->randomMachineName(),
      ]);
      $media->save();

      $this->mediaEntities[] = $media;
    }

    // Check that all created entities are present in the test view.
    $view = Views::getView('test_media_entity_bulk_form');
    $view->execute();
    $this->assertEqual(count($view->result), 5, 'All created media entities are present in the view.');

    $this->adminUser = $this->drupalCreateUser([
      'view media',
      'update any media',
      'delete any media',
    ]);
    $this->drupalLogin($this->adminUser);

    // Check the operations are accessible to the logged in user.
    $this->drupalGet('test-media-entity-bulk-form');
    $elements = $this->xpath('//select[@id="edit-action"]//option');
    // Current available actions: Delete, Save, Publish, Unpublish.
    $this->assertIdentical(count($elements), 4, 'All media operations are found.');
  }

  /**
   * Tests the media bulk form.
   */
  public function testBulkForm() {

    // Test unpublishing in bulk.
    $edit = [
      'media_bulk_form[0]' => TRUE,
      'media_bulk_form[1]' => TRUE,
      'media_bulk_form[2]' => TRUE,
      'action' => 'media_unpublish_action',
    ];
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    $this->assertText("Unpublish media was applied to 3 items");
    $media1_status = $this->loadMedia(1)->isPublished();
    $this->assertEqual(FALSE, $media1_status, 'First media entity was unpublished correctly.');
    $media2_status = $this->loadMedia(2)->isPublished();
    $this->assertEqual(FALSE, $media2_status, 'Second media entity was unpublished correctly.');
    $media3_status = $this->loadMedia(3)->isPublished();
    $this->assertEqual(FALSE, $media3_status, 'Third media entity was unpublished correctly.');

    // Test publishing in bulk.
    $edit = [
      'media_bulk_form[0]' => TRUE,
      'media_bulk_form[1]' => TRUE,
      'action' => 'media_publish_action',
    ];
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    $this->assertText("Publish media was applied to 2 items");
    $media1_status = $this->loadMedia(1)->isPublished();
    $this->assertEqual(TRUE, $media1_status, 'First media entity was published back correctly.');
    $media2_status = $this->loadMedia(2)->isPublished();
    $this->assertEqual(TRUE, $media2_status, 'Second media entity was published back correctly.');

    // Test deletion in bulk.
    $edit = [
      'media_bulk_form[0]' => TRUE,
      'media_bulk_form[1]' => TRUE,
      'action' => 'media_delete_action',
    ];
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));

    $this->assertText("Are you sure you want to delete these items?");
    $label1 = $this->loadMedia(1)->label();
    $this->assertRaw('<li>' . $label1 . '</li>');
    $label2 = $this->loadMedia(2)->label();
    $this->assertRaw('<li>' . $label2 . '</li>');

    $this->drupalPostForm(NULL, [], t('Delete'));

    $media = $this->loadMedia(1);
    $this->assertNull($media, 'Media 1 has been correctly deleted.');
    $media = $this->loadMedia(2);
    $this->assertNull($media, 'Media 2 has been correctly deleted.');

    $this->assertText('Deleted 2 media entities.');
  }

  /**
   * Load the specified media from the storage.
   *
   * @param int $id
   *   The media identifier.
   *
   * @return \Drupal\media_entity\MediaInterface
   *   The loaded media entity.
   */
  protected function loadMedia($id) {
    /** @var \Drupal\media_entity\MediaStorage $storage */
    $storage = $this->container->get('entity.manager')->getStorage('media');
    return $storage->loadUnchanged($id);
  }

}
