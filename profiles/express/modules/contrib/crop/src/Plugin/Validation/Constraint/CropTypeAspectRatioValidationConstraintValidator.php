<?php

namespace Drupal\crop\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if the crop type is valid.
 */
class CropTypeAspectRatioValidationConstraintValidator extends ConstraintValidator {

  /**
   * Validator 2.5 and upwards compatible execution context.
   *
   * @var \Symfony\Component\Validator\Context\ExecutionContextInterface
   */
  protected $context;

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\crop\Entity\CropType $value */
    $aspect_ratio = $value->getAspectRatio();
    if (!empty($aspect_ratio) && !preg_match($value::VALIDATION_REGEXP, $aspect_ratio)) {
      $this->context->buildViolation($constraint->message)
        ->atPath('aspect_ratio')
        ->addViolation();
    }
  }

}
