<?php

namespace Drupal\webform;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a webform options entity.
 */
interface WebformOptionsInterface extends ConfigEntityInterface {

  /**
   * Set options (YAML) value.
   *
   * @param array $options
   *   An renderable array of options.
   *
   * @return $this
   */
  public function setOptions(array $options);

  /**
   * Get options (YAML) as an associative array.
   *
   * @return array|bool
   *   Options as an associative array. Returns FALSE is options YAML is invalid.
   */
  public function getOptions();

  /**
   * Determine if the webform options has alter hooks.
   *
   * @return bool
   *   TRUE if the webform options has alter hooks.
   */
  public function hasAlterHooks();

  /**
   * Get webform element options.
   *
   * @param array $element
   *   A webform element.
   * @param string $property_name
   *   The element property containing the options. Defaults to #options,
   *   for webform_likert elements it is #answers.
   *
   * @return array
   *   An associative array of options.
   */
  public static function getElementOptions(array &$element, $property_name = '#options');

}
