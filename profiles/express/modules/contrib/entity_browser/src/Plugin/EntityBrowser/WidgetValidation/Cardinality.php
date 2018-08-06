<?php

namespace Drupal\entity_browser\Plugin\EntityBrowser\WidgetValidation;

use Drupal\entity_browser\Element\EntityBrowserElement;
use Drupal\entity_browser\WidgetValidationBase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Validates that the widget returns the appropriate number of elements.
 *
 * @EntityBrowserWidgetValidation(
 *   id = "cardinality",
 *   label = @Translation("Cardinality validator")
 * )
 */
class Cardinality extends WidgetValidationBase {

  /**
   * {@inheritdoc}
   */
  public function validate(array $entities, $options = []) {
    $violations = new ConstraintViolationList();

    // As this validation happens at a level above the individual entities,
    // we implement logic without using Constraint Plugins.
    $count = count($entities);
    $max = $options['cardinality'];
    if ($max !== EntityBrowserElement::CARDINALITY_UNLIMITED && $count > $max) {
      $message = $this->formatPlural($max, 'You can not select more than 1 entity.', 'You can not select more than @count entities.');
      $violation = new ConstraintViolation($message, $message, [], $count, '', $count);
      $violations->add($violation);
    }

    return $violations;
  }

}
