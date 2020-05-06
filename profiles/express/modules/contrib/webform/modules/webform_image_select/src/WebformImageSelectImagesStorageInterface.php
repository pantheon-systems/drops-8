<?php

namespace Drupal\webform_image_select;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Config\Entity\ImportableEntityStorageInterface;

/**
 * Provides an interface for webform image select images storage.
 */
interface WebformImageSelectImagesStorageInterface extends ConfigEntityStorageInterface, ImportableEntityStorageInterface {

  /**
   * Gets the names of all categories.
   *
   * @return string[]
   *   An array of translated categories, sorted alphabetically.
   */
  public function getCategories();

  /**
   * Get all webform image select images grouped by category.
   *
   * @return string[]
   *   An array of webform image select images grouped by category.
   */
  public function getImages();

  /**
   * Get list of webform that use the specified webform images.
   *
   * @param \Drupal\webform_image_select\WebformImageSelectImagesInterface $webform_images
   *   A webform image select images entity.
   *
   * @return array
   *   A list of webform that use the specified webform images.
   */
  public function getUsedByWebforms(WebformImageSelectImagesInterface $webform_images);

}
