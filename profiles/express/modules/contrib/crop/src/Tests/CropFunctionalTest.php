<?php

namespace Drupal\crop\Tests;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\crop\Entity\Crop;
use Drupal\crop\Entity\CropType;
use Drupal\file\Entity\File;
use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for crop API.
 *
 * @group crop
 */
class CropFunctionalTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['crop', 'file'];

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Test image style.
   *
   * @var \Drupal\image\ImageStyleInterface
   */
  protected $testStyle;

  /**
   * Test crop type.
   *
   * @var \Drupal\crop\CropInterface
   */
  protected $cropType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(['administer crop types', 'administer image styles']);

    // Create test image style.
    $this->testStyle = $this->container->get('entity.manager')->getStorage('image_style')->create([
      'name' => 'test',
      'label' => 'Test image style',
      'effects' => [],
    ]);
    $this->testStyle->save();
  }

  /**
   * Tests crop type crud pages.
   */
  public function testCropTypeCrud() {
    // Anonymous users don't have access to crop type admin pages.
    $this->drupalGet('admin/config/media/crop');
    $this->assertResponse(403);
    $this->drupalGet('admin/config/media/crop/add');
    $this->assertResponse(403);

    // Can access pages if logged in and no crop types exist.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/media/crop');
    $this->assertResponse(200);
    $this->assertText(t('No crop types available.'));
    $this->assertLink(t('Add crop type'));

    // Can access add crop type form.
    $this->clickLink(t('Add crop type'));
    $this->assertResponse(200);
    $this->assertUrl('admin/config/media/crop/add');

    // Create crop type.
    $crop_type_id = strtolower($this->randomMachineName());
    $edit = [
      'id' => $crop_type_id,
      'label' => $this->randomMachineName(),
      'description' => $this->randomGenerator->sentences(10),
    ];
    $this->drupalPostForm('admin/config/media/crop/add', $edit, t('Save crop type'));
    $this->assertRaw(t('The crop type %name has been added.', ['%name' => $edit['label']]));
    $this->cropType = CropType::load($crop_type_id);
    $this->assertUrl('admin/config/media/crop');
    $label = $this->xpath("//td[contains(concat(' ',normalize-space(@class),' '),' menu-label ')]");
    $this->assert(strpos($label[0]->asXML(), $edit['label']) !== FALSE, 'Crop type label found on listing page.');
    $this->assertText($edit['description']);

    // Check edit form.
    $this->clickLink(t('Edit'));
    $this->assertText(t('Edit @name crop type', ['@name' => $edit['label']]));
    $this->assertRaw($edit['id']);
    $this->assertFieldById('edit-label', $edit['label']);
    $this->assertRaw($edit['description']);

    // See if crop type appears on image effect configuration form.
    $this->drupalGet('admin/config/media/image-styles/manage/' . $this->testStyle->id() . '/add/crop_crop');
    $option = $this->xpath("//select[@id='edit-data-crop-type']/option");
    $this->assert(strpos($option[0]->asXML(), $edit['label']) !== FALSE, 'Crop type label found on image effect page.');
    $this->drupalPostForm('admin/config/media/image-styles/manage/' . $this->testStyle->id() . '/add/crop_crop', ['data[crop_type]' => $edit['id']], t('Add effect'));
    $this->assertText(t('The image effect was successfully applied.'));
    $this->assertText(t('Manual crop uses @name crop type', ['@name' => $edit['label']]));
    $this->testStyle = $this->container->get('entity.manager')->getStorage('image_style')->loadUnchanged($this->testStyle->id());
    $this->assertEqual($this->testStyle->getEffects()->count(), 1, 'One image effect added to test image style.');
    $effect_configuration = $this->testStyle->getEffects()->getIterator()->current()->getConfiguration();
    $this->assertEqual($effect_configuration['data'], ['crop_type' => $edit['id']], 'Manual crop effect uses correct image style.');

    // Tests the image URI is extended with shortened hash in case of image
    // style and corresponding crop existence.
    $this->doTestFileUriAlter();

    // Try to access edit form as anonymous user.
    $this->drupalLogout();
    $this->drupalGet('admin/config/media/crop/manage/' . $edit['id']);
    $this->assertResponse(403);
    $this->drupalLogin($this->adminUser);

    // Try to create crop type with same machine name.
    $this->drupalPostForm('admin/config/media/crop/add', $edit, t('Save crop type'));
    $this->assertText(t('The machine-readable name is already in use. It must be unique.'));

    // Delete crop type.
    $this->drupalGet('admin/config/media/crop');
    $this->assertLink('Test image style');
    $this->clickLink(t('Delete'));
    $this->assertText(t('Are you sure you want to delete the crop type @name?', ['@name' => $edit['label']]));
    $this->drupalPostForm('admin/config/media/crop/manage/' . $edit['id'] . '/delete', [], t('Delete'));
    $this->assertRaw(t('The crop type %name has been deleted.', ['%name' => $edit['label']]));
    $this->assertText(t('No crop types available.'));
  }

  /**
   * Asserts a shortened hash is added to the file URI.
   *
   * Tests crop_file_url_alter().
   */
  protected function doTestFileUriAlter() {
    // Get the test file.
    file_unmanaged_copy(drupal_get_path('module', 'crop') . '/tests/files/sarajevo.png', PublicStream::basePath());
    $file_uri = 'public://sarajevo.png';
    $file = File::create(['uri' => $file_uri, 'status' => FILE_STATUS_PERMANENT]);
    $file->save();

    /** @var \Drupal\crop\CropInterface $crop */
    $values = [
      'type' => $this->cropType->id(),
      'entity_id' => $file->id(),
      'entity_type' => $file->getEntityTypeId(),
      'uri' => 'public://sarajevo.png',
      'x' => '100',
      'y' => '150',
      'width' => '200',
      'height' => '250',
    ];
    $crop = Crop::create($values);
    $crop->save();

    // Test that the hash is appended both when a URL is created and passed
    // through file_create_url() and when a URL is created, without additional
    // file_create_url() calls.
    $shortened_hash = substr(md5(implode($crop->position()) . implode($crop->anchor())), 0, 8);

    // Build an image style derivative for the file URI.
    $image_style_uri = $this->testStyle->buildUri($file_uri);
    $image_style_uri_url = file_create_url($image_style_uri);
    $this->assertTrue(strpos($image_style_uri_url, $shortened_hash) !== FALSE, 'The image style URL contains a shortened hash.');

    // Build an image style URL.
    $image_style_url = $this->testStyle->buildUrl($file_uri);
    $this->assertTrue(strpos($image_style_url, $shortened_hash) !== FALSE, 'The image style URL contains a shortened hash.');

    // Update the crop to assert the hash has changed.
    $crop->setPosition('80', '80')->save();
    $old_hash = $shortened_hash;
    $new_hash = substr(md5(implode($crop->position()) . implode($crop->anchor())), 0, 8);
    $image_style_url = $this->testStyle->buildUrl($file_uri);
    $this->assertFalse(strpos($image_style_url, $old_hash) !== FALSE, 'The image style URL does not contain the old hash.');
    $this->assertTrue(strpos($image_style_url, $new_hash) !== FALSE, 'The image style URL contains an updated hash.');

    // Delete the file and the crop entity associated,
    // the crop entity are auto cleaned by crop_file_delete().
    $file->delete();

    // Check that the crop entity is correctly deleted.
    $this->assertFalse(Crop::cropExists($file_uri), 'The Crop entity was correctly deleted after file delete.');
  }

}
