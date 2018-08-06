<?php

namespace Drupal\Tests\video_embed_field\Kernel;

/**
 * Test helpers for loading entities for tests.
 */
trait EntityLoadTrait {

  /**
   * Load an entity by it's label.
   *
   * @param string $label
   *   The label of the entity to load.
   * @param string $entity_type
   *   The entity type to load.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A loaded entity.
   */
  protected function loadEntityByLabel($label, $entity_type = 'node') {
    $type_manager = \Drupal::entityTypeManager();
    $label_key = $type_manager->getDefinition($entity_type)->getKey('label');
    $entities = \Drupal::entityQuery($entity_type)->condition($label_key, $label, '=')->execute();
    return $type_manager->getStorage($entity_type)->load(array_shift($entities));
  }

}
