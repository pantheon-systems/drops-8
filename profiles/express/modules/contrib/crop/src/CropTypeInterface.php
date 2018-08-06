<?php

namespace Drupal\crop;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a crop type entity.
 */
interface CropTypeInterface extends ConfigEntityInterface {

  /**
   * Aspect ratio validation regexp.
   *
   * @var array
   */
  const VALIDATION_REGEXP = '#^[0-9]+:[0-9]+$#';

  /**
   * Get aspect ratio of this crop type.
   *
   * @return string|null
   *   The aspect ratio of this crop type.
   */
  public function getAspectRatio();

  /**
   * Returns a list of available crop type names.
   *
   * This list can include types that are queued for addition or deletion.
   *
   * @return string[]
   *   An array of crop type labels, keyed by the crop type name.
   */
  public static function getCropTypeNames();

  /**
   * Validates the currently set values.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationListInterface
   *   A list of constraint violations. If the list is empty, validation
   *   succeeded.
   */
  public function validate();

  /**
   * Returns width and height soft limit values.
   *
   * @return array
   *   Width and height values.
   */
  public function getSoftLimit();

  /**
   * Returns width and height hard limit values.
   *
   * @return array
   *   Width and height values.
   */
  public function getHardLimit();

}
