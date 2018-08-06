<?php

namespace Drupal\Tests\crop\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\crop\Entity\Crop;

/**
 * Tests the crop entity CRUD operations.
 *
 * @group crop
 */
class CropCRUDTest extends CropUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['user', 'image', 'crop', 'file', 'system'];

  /**
   * Tests crop type save.
   */
  public function testCropTypeSave() {
    $values = [
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
      'description' => $this->randomGenerator->sentences(8),
    ];
    $crop_type = $this->cropTypeStorage->create($values);

    try {
      $crop_type->save();
      $this->assertTrue(TRUE, 'Crop type saved correctly.');
    }
    catch (\Exception $exception) {
      $this->assertTrue(FALSE, 'Crop type not saved correctly.');
    }

    $loaded = $this->container->get('config.factory')->get('crop.type.' . $values['id'])->get();
    foreach ($values as $key => $value) {
      $this->assertEquals($loaded[$key], $value, new FormattableMarkup('Correct value for @field found.', ['@field' => $key]));
    }
  }

  /**
   * Tests crop save.
   */
  public function testCropSave() {
    // Test file.
    $file = $this->getTestFile();
    $file->save();

    /** @var \Drupal\crop\CropInterface $crop */
    $values = [
      'type' => $this->cropType->id(),
      'entity_id' => $file->id(),
      'entity_type' => $file->getEntityTypeId(),
      'uri' => $file->getFileUri(),
      'x' => '100',
      'y' => '150',
      'width' => '200',
      'height' => '250',
    ];
    $crop = $this->cropStorage->create($values);

    $this->assertEquals(['x' => 100, 'y' => 150], $crop->position(), t('Position correctly returned.'));
    $this->assertEquals(['width' => 200, 'height' => 250], $crop->size(), t('Size correctly returned.'));
    $this->assertEquals(['x' => 0, 'y' => 25], $crop->anchor(), t('Anchor correctly returned.'));

    $crop->setPosition(10, 10);
    $crop->setSize(20, 20);

    $this->assertEquals(['x' => 10, 'y' => 10], $crop->position(), t('Position correctly returned.'));
    $this->assertEquals(['width' => 20, 'height' => 20], $crop->size(), t('Size correctly returned.'));
    $this->assertEquals(['x' => 0, 'y' => 0], $crop->anchor(), t('Anchor correctly returned.'));

    $crop->setPosition($values['x'], $values['y']);
    $crop->setSize($values['width'], $values['height']);

    try {
      $crop->save();
      $this->assertTrue(TRUE, 'Crop saved correctly.');
    }
    catch (\Exception $exception) {
      $this->assertTrue(FALSE, 'Crop not saved correctly.');
    }

    $loaded_crop = $this->cropStorage->loadUnchanged(1);
    foreach ($values as $key => $value) {
      switch ($key) {
        case 'type':
          $this->assertEquals($loaded_crop->{$key}->target_id, $value, new FormattableMarkup('Correct value for @field found.', ['@field' => $key]));
          break;

        default:
          $this->assertEquals($loaded_crop->{$key}->value, $value, new FormattableMarkup('Correct value for @field found.', ['@field' => $key]));
          break;
      }
    }

    $this->assertTrue(Crop::cropExists($file->getFileUri()), t('Crop::cropExists() correctly found saved crop.'));
    $this->assertTrue(Crop::cropExists($file->getFileUri(), $this->cropType->id()), t('Crop::cropExists() correctly found saved crop.'));
    $this->assertFalse(Crop::cropExists($file->getFileUri(), 'nonexistent_type'), t('Crop::cropExists() correctly handled wrong type.'));
    $this->assertFalse(Crop::cropExists('public://nonexistent.png'), t('Crop::cropExists() correctly handled wrong uri.'));

    $loaded_crop = Crop::findCrop($file->getFileUri(), $this->cropType->id());
    $this->assertEquals($crop->id(), $loaded_crop->id(), t('Crop::findCrop() correctly loaded crop entity.'));
    $this->assertEquals($crop->position(), $loaded_crop->position(), t('Crop::findCrop() correctly loaded crop entity.'));
    $this->assertEquals($crop->size(), $loaded_crop->size(), t('Crop::findCrop() correctly loaded crop entity.'));
    $this->assertEquals($crop->uri->value, $loaded_crop->uri->value, t('Crop::findCrop() correctly loaded crop entity.'));
    $this->assertNull(Crop::findCrop('public://nonexistent.png', $this->cropType->id()), t('Crop::findCrop() correctly handled nonexistent crop.'));
    $this->assertNull(Crop::findCrop('public://nonexistent.png', 'nonexistent_crop'), t('Crop::findCrop() correctly handled nonexistent crop.'));
  }

  /**
   * Tests automatic removal of orphaned crops.
   */
  public function testOrphanRemoval() {
    $this->installSchema('file', ['file_usage']);
    $file = $this->getTestFile();
    $file->save();

    $values = [
      'type' => $this->cropType->id(),
      'entity_id' => $file->id(),
      'entity_type' => $file->getEntityTypeId(),
      'x' => '100',
      'y' => '150',
      'width' => '200',
      'height' => '250',
    ];
    /** @var \Drupal\crop\CropInterface $crop */
    $crop = $this->cropStorage->create($values);
    $crop->save();

    // Check if the crop is automatically removed at file removal.
    $file->delete();
    $crops = $this->cropStorage->loadByProperties(['uri' => $crop->uri->value]);
    $this->assertEquals([], $crops, 'Crop deleted correctly.');
  }

}
