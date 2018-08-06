<?php

namespace Drupal\pathauto;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\path\Plugin\Field\FieldType\PathItem;

/**
 * Extends the default PathItem implementation to generate aliases.
 */
class PathautoItem extends PathItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $properties['pathauto'] = DataDefinition::create('integer')
      ->setLabel(t('Pathauto state'))
      ->setDescription(t('Whether an automated alias should be created or not.'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\pathauto\PathautoState');
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    // Only allow the parent implementation to act if pathauto will not create
    // an alias.
    if ($this->pathauto == PathautoState::SKIP) {
      parent::postSave($update);
    }
    $this->get('pathauto')->persist();
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    // Make sure that the pathauto state flag does not get lost if just that is
    // changed.
    return !$this->alias && !$this->get('pathauto')->hasValue();
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    parent::applyDefaultValue($notify);
    // Created fields default creating a new alias.
    $this->setValue(array('pathauto' => PathautoState::CREATE), $notify);
    return $this;
  }

}
