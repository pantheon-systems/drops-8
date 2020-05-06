<?php

namespace Drupal\webform_options_custom;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a webform options custom entity.
 */
interface WebformOptionsCustomInterface extends ConfigEntityInterface {

  /**
   * Custom options from URL.
   */
  const TYPE_URL = 'url';

  /**
   * Custom options from HTML/SVG template.
   */
  const TYPE_TEMPLATE = 'template';

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
   *   Images as an associative array. Returns FALSE if options YAML is invalid.
   */
  public function getOptions();

  /**
   * Set a custom options element HTML/SVG template.
   *
   * @return string
   *   A custom options element HTML/SVG template.
   */
  public function getTemplate();

  /**
   * Set a custom options element template URL.
   *
   * @return string
   *   A custom options element template URL.
   */
  public function getUrl();

  /**
   * Get the custom options element.
   *
   * @return array
   *   The custom options element.
   */
  public function getElement();

  /**
   * Get the custom options element preview.
   *
   * @return array
   *   The custom options element preview.
   */
  public function getPreview();

  /**
   * Get template custom options.
   *
   * @return array
   *   A templates custom options.
   */
  public function getTemplateOptions();

}
