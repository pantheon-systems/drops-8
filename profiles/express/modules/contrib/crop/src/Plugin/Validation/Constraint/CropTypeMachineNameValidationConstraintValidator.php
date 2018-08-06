<?php

namespace Drupal\crop\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if the crop type is valid.
 */
class CropTypeMachineNameValidationConstraintValidator extends ConstraintValidator {

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
    // '0' is invalid, since elsewhere we check it using empty().
    /** @var \Drupal\crop\Entity\CropType $value */
    if (trim($value->id()) == '0') {
      $this->context->buildViolation($constraint->message)
        ->atPath('id')
        ->addViolation();
    }
  }

}
