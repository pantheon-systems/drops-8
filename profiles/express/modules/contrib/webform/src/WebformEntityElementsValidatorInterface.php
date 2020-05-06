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
   * @param array $options
   *   An array of validation rules to check.
   *
   * @return array|null
   *   An array of error messages or NULL if the elements are valid.
   */
  public function validate(WebformInterface $webform, array $options = []);

}
