<?php

namespace Drupal\focal_point\Plugin\ImageEffect;

use Drupal\focal_point\FocalPointEffectBase;
use Drupal\Core\Image\ImageInterface;

/**
 * Crops image while keeping its focal point as close to centered as possible.
 *
 * @ImageEffect(
 *   id = "focal_point_crop",
 *   label = @Translation("Focal Point Crop"),
 *   description = @Translation("Crops image while keeping its focal point as close to centered as possible.")
 * )
 */
class FocalPointCropImageEffect extends FocalPointEffectBase {

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function applyEffect(ImageInterface $image) {
    parent::applyEffect($image);

    $crop = $this->getCrop($image);
    return $this->applyCrop($image, $crop);
  }

}
