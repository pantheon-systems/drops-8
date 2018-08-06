<?php

namespace Drupal\entity_browser\Plugin\EntityBrowser\WidgetValidation;

use Drupal\entity_browser\WidgetValidationBase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Validates a file based on passed validators.
 *
 * @EntityBrowserWidgetValidation(
 *   id = "file",
 *   label = @Translation("File validator")
 * )
 */
class File extends WidgetValidationBase {

  /**
   * {@inheritdoc}
   */
  public function validate(array $entities, $options = []) {
    $violations = new ConstraintViolationList();

    // We implement the same logic as \Drupal\file\Plugin\Validation\Constraint\FileValidationConstraintValidator
    // here as core does not always write constraints with non-form use cases
    // in mind.
    foreach ($entities as $entity) {
      if (isset($options['validators'])) {
        // Checks that a file meets the criteria specified by the validators.
        if ($errors = file_validate($entity, $options['validators'])) {
          foreach ($errors as $error) {
            $violation = new ConstraintViolation($error, $error, [], $entity, '', $entity);
            $violations->add($violation);
          }
        }
      }
    }

    return $violations;
  }

}
