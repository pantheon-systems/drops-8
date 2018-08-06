<?php

namespace Drupal\diff\Plugin\diff\Field;

use Drupal\diff\FieldDiffBuilderBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

const COMPARE_ENTITY_REFERENCE_ID = 0;
const COMPARE_ENTITY_REFERENCE_LABEL = 1;


/**
 * Plugin to diff entity reference fields.
 *
 * @FieldDiffBuilder(
 *   id = "entity_reference_field_diff_builder",
 *   label = @Translation("Entity Reference Field Diff"),
 *   field_types = {
 *     "entity_reference"
 *   },
 * )
 */
class EntityReferenceFieldBuilder extends FieldDiffBuilderBase {

  /**
   * {@inheritdoc}
   */
  public function build(FieldItemListInterface $field_items) {
    $result = array();
    // Every item from $field_items is of type FieldItemInterface.
    foreach ($field_items as $field_key => $field_item) {
      if (!$field_item->isEmpty()) {
        $values = $field_item->getValue();
        // Compare entity ids.
        if ($field_item->entity) {
          if ($this->configuration['compare_entity_reference'] == COMPARE_ENTITY_REFERENCE_LABEL) {
            /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
            $entity = $field_item->entity;
            $result[$field_key][] = $entity->label();
          }
          else {
            $result[$field_key][] = $this->t('Entity ID: :id', [
              ':id' => $values['target_id'],
            ]);
          }
        }
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['compare_entity_reference'] = array(
      '#type' => 'select',
      '#title' => $this->t('Compare'),
      '#options' => array(COMPARE_ENTITY_REFERENCE_ID => t('ID'), COMPARE_ENTITY_REFERENCE_LABEL => t('Label')),
      '#default_value' => $this->configuration['compare_entity_reference'],
    );

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['compare_entity_reference'] = $form_state->getValue('compare_entity_reference');

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_configuration = array(
      'compare_entity_reference' => COMPARE_ENTITY_REFERENCE_LABEL,
    );
    $default_configuration += parent::defaultConfiguration();

    return $default_configuration;
  }

}
