<?php

namespace Drupal\pathauto\Form;

use Drupal\Core\Entity\EntityInterface;

/**
 * Provides the pathauto pattern duplicate form.
 */
class PatternDuplicateForm extends PatternEditForm {

  /**
   * {@inheritdoc}
   */
  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity->createDuplicate();
    return $this;
  }

}
