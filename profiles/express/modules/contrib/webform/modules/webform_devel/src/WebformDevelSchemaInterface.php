<?php

namespace Drupal\webform_devel;

use Drupal\webform\WebformInterface;

/**
 * Provides an interface defining a webform devel schema.
 */
interface WebformDevelSchemaInterface {

  /**
   * Get webform schema columns.
   *
   * @return array
   *   Webform schema columns.
   */
  public function getColumns();

  /**
   * Get webform elements schema.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return array
   *   An array containing the schema for the webform's elements.
   */
  public function getElements(WebformInterface $webform);

}
