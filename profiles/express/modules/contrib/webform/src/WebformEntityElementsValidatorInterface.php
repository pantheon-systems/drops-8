<?php

namespace Drupal\webform;

/**
 * Defines an interface for elements validator.
 */
interface WebformEntityElementsValidatorInterface {

  /**
   * Validate webform elements.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return array|null
   *   An array of error messages or NULL is the elements are valid.
   */
  public function validate(WebformInterface $webform);

}