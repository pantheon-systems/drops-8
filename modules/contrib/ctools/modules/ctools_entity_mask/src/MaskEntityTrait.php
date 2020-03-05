<?php

namespace Drupal\ctools_entity_mask;

/**
 * Provides common functionality for mask entities.
 */
trait MaskEntityTrait {

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::id().
   *
   * Mask entities are generally not saved to the database like standard content
   * entities, so it cannot be assumed that they will have a serial ID at any
   * point in their lives. However, Drupal still expects all entities to have an
   * identifier of some kind, so this dual-purposes the UUID as the canonical
   * entity ID. (It would be nice if core did this as a rule for all entities
   * and stopped using serial IDs, but, y'know, baby steps.)
   *
   * @return string
   */
  public function id() {
    return $this->uuid();
  }

}
