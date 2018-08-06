<?php

namespace Drupal\crop\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Supports validating crop type machine name.
 *
 * @Constraint(
 *   id = "CropTypeMachineNameValidation",
 *   label = @Translation("Crop Type machine name", context = "Validation")
 * )
 */
class CropTypeMachineNameValidationConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'Invalid machine-readable name.';

}
