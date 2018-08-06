<?php

namespace Drupal\webform;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Config\Entity\ImportableEntityStorageInterface;

/**
 * Provides an interface for Webform Options storage.
 */
interface WebformOptionsStorageInterface extends ConfigEntityStorageInterface, ImportableEntityStorageInterface {

  /**
   * Gets the names of all categories.
   *
   * @return string[]
   *   An array of translated categories, sorted alphabetically.
   */
  public function getCategories();

  /**
   * Get all options grouped by category.
   *
   * @return string[]
   *   An array of options grouped by category.
   */
  public function getOptions();

  /**
   * Get all likert options with 'Likert:' prefix removed..
   *
   * @return string[]
   *   An array of likert options.
   */
  public function getLikerts();

}
