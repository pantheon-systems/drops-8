<?php

namespace Drupal\webform_image_select;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a webform image select images entity.
 */
interface WebformImageSelectImagesInterface extends ConfigEntityInterface {

  /**
   * Set images (YAML) value.
   *
   * @param array $images
   *   An renderable array of images.
   *
   * @return $this
   */
  public function setImages(array $images);

  /**
   * Get images (YAML) as an associative array.
   *
   * @return array|bool
   *   Images as an associative array. Returns FALSE if images YAML is invalid.
   */
  public function getImages();

  /**
   * Get webform image select element images.
   *
   * @param array $element
   *   A webform image select element.
   *
   * @return array
   *   An associative array of images.
   */
  public static function getElementImages(array &$element);

}
