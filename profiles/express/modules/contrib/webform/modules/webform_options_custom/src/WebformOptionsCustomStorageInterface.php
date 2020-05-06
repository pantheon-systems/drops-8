<?php

namespace Drupal\webform_options_custom;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Config\Entity\ImportableEntityStorageInterface;

/**
 * Provides an interface for webform options custom storage.
 */
interface WebformOptionsCustomStorageInterface extends ConfigEntityStorageInterface, ImportableEntityStorageInterface {

  /**
   * Gets the names of all categories.
   *
   * @return string[]
   *   An array of translated categories, sorted alphabetically.
   */
  public function getCategories();

  /**
   * Get all webform options custom grouped by category.
   *
   * @return string[]
   *   An array of webform options custom grouped by category.
   */
  public function getOptionsCustom();

  /**
   * Get list of webform that use the specified webform custom options.
   *
   * @param \Drupal\webform_options_custom\WebformOptionsCustomInterface $webform_options_custom
   *   A webform options custom entity.
   *
   * @return array
   *   A list of webform that use the specified webform custom options
   */
  public function getUsedByWebforms(WebformOptionsCustomInterface $webform_options_custom);

}
