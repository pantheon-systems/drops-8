<?php

namespace Drupal\webform\Plugin\Field\FieldType;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\webform\WebformInterface;

/**
 * Defines the 'webform_entity_reference' entity field type.
 *
 * Extends EntityReferenceItem and only support targeting webform entities.
 *
 * @FieldType(
 *   id = "webform",
 *   label = @Translation("Webform"),
 *   description = @Translation("A webform containing default submission values."),
 *   category = @Translation("Reference"),
 *   default_widget = "webform_entity_reference_select",
 *   default_formatter = "webform_entity_reference_entity_view",
 *   list_class = "\Drupal\webform\Plugin\Field\FieldType\WebformEntityReferenceFieldItemList",
 * )
 */
class WebformEntityReferenceItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'target_type' => 'webform',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'default_data' => '',
      'status' => WebformInterface::STATUS_OPEN,
      'open' => '',
      'close' => '',
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'target_id' => [
          'description' => 'The ID of the webform entity.',
          'type' => 'varchar_ascii',
          'length' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
        ],
        'default_data' => [
          'description' => 'Default submission data.',
          'type' => 'text',
        ],
        'status' => [
          'description' => 'Flag to control whether this webform should be open, closed, or scheduled for new submissions.',
          'type' => 'varchar',
          'length' => 20,
        ],
        'open' => [
          'description' => 'The open date/time.',
          'type' => 'varchar',
          'length' => 20,
        ],
        'close' => [
          'description' => 'The close date/time.',
          'type' => 'varchar',
          'length' => 20,
        ],
      ],
      'indexes' => [
        'target_id' => ['target_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['default_data'] = DataDefinition::create('string')
      ->setLabel(t('Default submission data'));

    $properties['status'] = DataDefinition::create('string')
      ->setLabel(t('Status'))
      ->setDescription(t('Flag to control whether this webform should be open or closed to new submissions.'));

    $properties['open'] = DataDefinition::create('datetime_iso8601')
      ->setLabel(t('Open value'));

    $properties['close'] = DataDefinition::create('datetime_iso8601')
      ->setLabel(t('Close value'));

    return $properties;
  }

  /**
   * Get an entity's webform field name.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A fieldable content entity.
   *
   * @return string
   *   The name of the webform field or an empty string.
   */
  public static function getEntityWebformFieldName(EntityInterface $entity = NULL) {
    if ($entity === NULL || !method_exists($entity, 'hasField')) {
      return '';
    }

    if ($entity instanceof ContentEntityInterface) {
      $fields = $entity->getFieldDefinitions();
      foreach ($fields as $field_name => $field_definition) {
        if ($field_definition->getType() == 'webform') {
          return $field_name;
        }
      }
    }
    return '';
  }

  /**
   * Get an entity's target webform.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A fieldable content entity.
   *
   * @return \Drupal\webform\WebformInterface|null
   *   The entity's target webform or NULL.
   */
  public static function getEntityWebformTarget(EntityInterface $entity = NULL) {
    if ($field_name = self::getEntityWebformFieldName($entity)) {
      return $entity->$field_name->entity;
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function getPreconfiguredOptions() {
    return [];
  }

}
