<?php

namespace Drupal\responsive_preview;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Device entities.
 */
interface DeviceInterface extends ConfigEntityInterface {

  /**
   * Gets the device weight.
   *
   * @return int
   *   The device weight.
   */
  public function getWeight();

  /**
   * Sets the device weight.
   *
   * @param int $weight
   *   The device weight.
   */
  public function setWeight($weight);

  /**
   * Gets the device orientation.
   *
   * @return string
   *   The device orientation.
   */
  public function getOrientation();

  /**
   * Sets the device orientation.
   *
   * @param string $orientation
   *   The device orientation. The only values allowed are: 'landscape'
   *   and 'portrait'.
   */
  public function setOrientation($orientation);

  /**
   * Gets the device dimension.
   *
   * @return array
   *   Associative array containing the following properties:
   *   - weight: the width (integer).
   *   - height: the height (integer).
   *   - dppx: the dots per pixel (integer).
   */
  public function getDimensions();

  /**
   * Sets the device dimension.
   *
   * @param array $dimensions
   *   Associative array containing the following properties:
   *   - weight: the width (integer).
   *   - height: the height (integer).
   *   - dppx: the dots per pixel (integer).
   */
  public function setDimensions(array $dimensions);

}
