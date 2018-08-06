<?php

namespace Drupal\auto_entitylabel\Plugin\Validation;

use Drupal\Core\Validation\Plugin\Validation\Constraint\NotNullConstraintValidator;
use Drupal\Core\Field\FieldItemList;
use Symfony\Component\Validator\Constraint;

/**
 * EntityLabelNotNull constraint validator.
 *
 * Custom override of NotNull constraint to allow empty entity labels to
 * validate before the automatic label is set.
 */
class EntityLabelNotNullConstraintValidator extends NotNullConstraintValidator {
  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    $typed_data = $this->getTypedData();
    if ($typed_data instanceof FieldItemList && $typed_data->isEmpty()) {
      $entity = $typed_data->getEntity();
      $decorator = \Drupal::service('auto_entitylabel.entity_decorator');
      /** @var \Drupal\auto_entitylabel\AutoEntityLabelManager $decorated_entity */
      $decorated_entity = $decorator->decorate($entity);

      if ($decorated_entity->hasLabel() && $decorated_entity->autoLabelNeeded()) {
        return;
      }
    }
    parent::validate($value, $constraint);
  }
}
