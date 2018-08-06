<?php

namespace Drupal\entity_embed\Plugin\entity_embed\EntityEmbedDisplay;

use Drupal\entity_embed\EntityEmbedDisplay\FieldFormatterEntityEmbedDisplayBase;

/**
 * Entity Embed Display reusing entity reference field formatters.
 *
 * @see \Drupal\entity_embed\EntityEmbedDisplay\EntityEmbedDisplayInterface
 *
 * @EntityEmbedDisplay(
 *   id = "entity_reference",
 *   label = @Translation("Entity Reference"),
 *   deriver = "Drupal\entity_embed\Plugin\Derivative\FieldFormatterDeriver",
 *   field_type = "entity_reference"
 * )
 */
class EntityReferenceFieldFormatter extends FieldFormatterEntityEmbedDisplayBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinition() {
    if (!isset($this->fieldDefinition)) {
      $this->fieldDefinition = parent::getFieldDefinition();
      $this->fieldDefinition->setSetting('target_type', $this->getEntityTypeFromContext());
    }
    return $this->fieldDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldValue() {
    return array('target_id' => $this->getContextValue('entity')->id());
  }

}
