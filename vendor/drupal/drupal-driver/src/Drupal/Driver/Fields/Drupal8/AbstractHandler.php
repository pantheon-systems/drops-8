<?php

namespace Drupal\Driver\Fields\Drupal8;

use Drupal\Driver\Fields\FieldHandlerInterface;

/**
 * Base class for field handlers in Drupal 8.
 */
abstract class AbstractHandler implements FieldHandlerInterface {
  /**
   * Field storage definition.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldInfo = NULL;

  /**
   * Field configuration definition.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $fieldConfig = NULL;

  /**
   * Constructs an AbstractHandler object.
   *
   * @param object $entity
   *   The simulated entity object containing field information.
   * @param string $entity_type
   *   The entity type.
   * @param string $field_name
   *   The field name.
   *
   * @throws \Exception
   *   Thrown when the given field name does not exist on the entity.
   */
  public function __construct(\stdClass $entity, $entity_type, $field_name) {
    if (empty($entity_type)) {
      throw new \Exception("You must specify an entity type in order to parse entity fields.");
    }

    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = \Drupal::service('entity_field.manager');
    $fields = $entity_field_manager->getFieldStorageDefinitions($entity_type);
    $this->fieldInfo = $fields[$field_name];

    // The bundle may be stored either under "step_bundle" or under the name
    // of the entity's bundle key. If both are empty, assume this is a single
    // bundle entity, and therefore make the bundle name the entity type.
    $bundle_key = \Drupal::entityTypeManager()->getDefinition($entity_type)->getKey('bundle');
    $bundle = !empty($entity->$bundle_key) ? $entity->$bundle_key : (isset($entity->step_bundle) ? $entity->step_bundle : $entity_type);

    $fields = $entity_field_manager->getFieldDefinitions($entity_type, $bundle);
    $fieldsstring = '';
    foreach ($fields as $key => $value) {
      $fieldsstring = $fieldsstring . ", " . $key;
    }
    if (empty($fields[$field_name])) {
      throw new \Exception(sprintf('The field "%s" does not exist on entity type "%s" bundle "%s".', $field_name, $entity_type, $bundle));
    }
    $this->fieldConfig = $fields[$field_name];
  }

}
