<?php

namespace Drupal\entity_browser\Events;

/**
 * Represents entity selection as event.
 */
class EntitySelectionEvent extends EventBase {

  /**
   * Entities being selected.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected $entities;

  /**
   * Constructs a EntitySelectionEvent object.
   *
   * @param string $entity_browser_id
   *   Entity browser ID.
   * @param string $instance_uuid
   *   Entity browser instance UUID.
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   Array of selected entities.
   */
  public function __construct($entity_browser_id, $instance_uuid, array $entities) {
    parent::__construct($entity_browser_id, $instance_uuid);
    $this->entities = $entities;
  }

  /**
   * Gets selected entities.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   */
  public function getEntities() {
    return $this->entities;
  }

}
