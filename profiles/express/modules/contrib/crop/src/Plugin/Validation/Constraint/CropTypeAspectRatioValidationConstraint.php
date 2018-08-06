<?php

namespace Drupal\crop\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Supports validating crop type aspect ratio.
 *
 * @Constraint(
 *   id = "CropTypeAspectRatioValidation",
 *   label = @Translation("Crop Type aspect ratio", context = "Validation")
 * )
 */
class CropTypeAspectRatioValidationConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'Invalid aspect ratio format. Should be defined in H:W form.';

}
