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

  /**
   * Returns the next serial number.
   *
   * @return int
   *   The next serial number.
   */
  public function getNextSerial(WebformInterface $webform);

  /**
   * Set the next serial number.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param int $next_serial
   *   The next serial number.
   */
  public function setNextSerial(WebformInterface $webform, $next_serial);

  /**
   * Returns the next serial number for a webform's submission.
   *
   * @return int
   *   The next serial number for a webform's submission.
   */
  public function getSerial(WebformInterface $webform);

  /**
   * Returns a webform's max serial number.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return int
   *   The next serial number.
   */
  public function getMaxSerial(WebformInterface $webform);

  /**
   * Get total results for all webforms.
   *
   * @return array
   *   An associative array keyed by webform id contains total results for
   *   all webforms.
   */
  public function getTotalNumberOfResults();

}
