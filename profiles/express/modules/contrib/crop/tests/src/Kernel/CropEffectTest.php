<?php

namespace Drupal\Tests\crop\Kernel;

/**
 * Tests the crop image effect.
 *
 * @group crop
 */
class CropEffectTest extends CropUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['user', 'image', 'crop', 'file', 'system'];

  /**
   * Tests manual crop image effect.
   */
  public function testCropEffect() {
    // Create image to be cropped.
    $file = $this->getTestFile();
    $file->save();

    // Create crop.
    $values = [
      'type' => $this->cropType->id(),
      'entity_id' => $file->id(),
      'entity_type' => 'file',
      'uri' => $file->getFileUri(),
      'x' => '190',
      'y' => '120',
      'width' => '50',
      'height' => '50',
    ];
    /** @var \Drupal\crop\CropInterface $crop */
    $crop = $this->container->get('entity.manager')->getStorage('crop')->create($values);
    $crop->save();

    $derivative_uri = $this->testStyle->buildUri($file->getFileUri());
    $this->testStyle->createDerivative($file->getFileUri(), $derivative_uri);

    $this->assertTrue(file_exists($derivative_uri), 'Image derivative file exists on the filesystem.');

    // Test if cropped version looks like expected. Basically loop pixels,
    // in derivative image and check if they look the same as pixels,
    // in corresponding region on original image.
    $original_image = imagecreatefrompng($file->getFileUri());
    $derivative_image = imagecreatefrompng($derivative_uri);
    $orig_start = $crop->anchor();
    $matches = TRUE;
    for ($x = 0; $x < $values['width']; $x++) {
      for ($y = 0; $y < $values['height']; $y++) {
        if (imagecolorat($derivative_image, $x, $y) != imagecolorat($original_image, $orig_start['x'] + $x, $orig_start['y'] + $y)) {
          $matches = FALSE;
          break;
        }
      }
    }
    $this->assertTrue($matches, 'Cropped image looks the same as region on original.');
  }

  /**
   * Test image crop effect dimensions.
   */
  public function testCropDimenssions() {
    // Create image to be cropped.
    $file = $this->getTestFile();
    $file->save();
    $file_uri = $file->getFileUri();

    // Create crop.
    $values = [
      'type' => $this->cropType->id(),
      'entity_id' => $file->id(),
      'entity_type' => 'file',
      'uri' => $file_uri,
      'x' => '190',
      'y' => '120',
      'width' => '50',
      'height' => '50',
    ];
    $dimensions = ['width' => 0, 'height' => 0];

    /** @var \Drupal\crop\CropInterface $crop */
    $crop = $this->container->get('entity_type.manager')->getStorage('crop')->create($values);
    $crop->save();

    /** @var $effect \Drupal\crop\Plugin\ImageEffect\CropEffect */
    $effect = $this->imageEffectManager->createInstance('crop_crop', ['data' => ['crop_type' => $this->cropType->id()]]);
    $effect->transformDimensions($dimensions, $file_uri);

    $this->assertEquals($crop->size(), $dimensions, t('CropEffect::transformDimensions() transform image dimensions correctly.'));
  }

}
