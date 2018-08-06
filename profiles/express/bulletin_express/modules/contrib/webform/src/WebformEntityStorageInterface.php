<?php

namespace Drupal\webform;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Config\Entity\ImportableEntityStorageInterface;

/**
 * Provides an interface for Webform storage.
 */
interface WebformEntityStorageInterface extends ConfigEntityStorageInterface, ImportableEntityStorageInterface {

  /**
   * Gets the names of all categories.
   *
   * @param null|bool $template
   *   If TRUE only template categories will be returned.
   *   If FALSE only webform categories will be returned.
   *   If NULL all categories will be returned.
   *
   * @return string[]
   *   An array of translated categories, sorted alphabetically.
   */
  public function getCategories($template = NULL);

  /**
   * Get all webforms grouped by category.
   *
   * @param null|bool $template
   *   If TRUE only template categories will be returned.
   *   If FALSE only webform categories will be returned.
   *   If NULL all categories will be returned.
   *
   * @return string[]
   *   An array of options grouped by category.
   */
  public function getOptions($template = NULL);

}
