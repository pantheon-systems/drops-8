<?php

namespace Drupal\focal_point;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\crop\CropInterface;
use Drupal\crop\Entity\Crop;
use Drupal\file\FileInterface;

/**
 * Provides business logic related to focal point.
 */
class FocalPointManager implements FocalPointManagerInterface {

  /**
   * Regular expression for focal point form value validation.
   *
   * @var string
   */
  const FOCAL_POINT_VALIDATION_REGEXP = '/^(100|[0-9]{1,2})(,)(100|[0-9]{1,2})$/';

  /**
   * Crop entity storage.
   *
   * @var \Drupal\crop\CropStorageInterface
   */
  protected $cropStorage;

  /**
   * Constructs FocalPointManager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->cropStorage = $entity_type_manager->getStorage('crop');
  }

  /**
   * {@inheritdoc}
   */
  public function validateFocalPoint($focal_point) {
    return (bool) preg_match(static::FOCAL_POINT_VALIDATION_REGEXP, $focal_point);
  }

  /**
   * {@inheritdoc}
   */
  public function relativeToAbsolute($x, $y, $width, $height) {
    // Focal point JS provides relative location while crop entity
    // expects exact coordinate on the original image. Let's convert.
    return [
      'x' => (int) round(($x / 100.0) * $width),
      'y' => (int) round(($y / 100.0) * $height),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function absoluteToRelative($x, $y, $width, $height) {
    return [
      'x' => (int) round($x / $width * 100),
      'y' => (int) round($y / $height * 100),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCropEntity(FileInterface $file, $crop_type) {
    if (Crop::cropExists($file->getFileUri(), $crop_type)) {
      /** @var \Drupal\crop\CropInterface $crop */
      $crop = Crop::findCrop($file->getFileUri(), $crop_type);
    }
    else {
      $values = [
        'type' => $crop_type,
        'entity_id' => $file->id(),
        'entity_type' => 'file',
        'uri' => $file->getFileUri(),
      ];

      $crop = \Drupal::entityTypeManager()
        ->getStorage('crop')
        ->create($values);
    }

    return $crop;
  }

  /**
   * {@inheritdoc}
   */
  public function saveCropEntity($x, $y, $width, $height, CropInterface $crop) {
    $absolute = $this->relativeToAbsolute($x, $y, $width, $height);

    $anchor = $crop->anchor();
    if ($anchor['x'] != $absolute['x'] || $anchor['y'] != $absolute['y']) {
      $crop->setPosition($absolute['x'], $absolute['y']);
      $crop->save();
    }

    return $crop;
  }

}
