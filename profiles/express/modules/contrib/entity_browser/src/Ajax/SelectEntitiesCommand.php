<?php

namespace Drupal\entity_browser\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command to rerender a formatted text field without any transformation
 * filters.
 */
class SelectEntitiesCommand implements CommandInterface {

  /**
   * A unique identifier.
   *
   * @var string
   */
  protected $uuid;

  /**
   * A CSS selector string.
   *
   * @var array
   */
  protected $entities;

  /**
   * Constructs a \Drupal\entity_browser\Ajax\SelectEntities object.
   *
   * @param string $uuid
   *   Entity browser instance UUID.
   * @param array $entities
   *   Entities that were selected. Each entity is represented with an array
   *   consisting of three values (entity ID, entity UUID and entity type).
   */
  public function __construct($uuid, $entities) {
    $this->uuid = $uuid;
    $this->entities = $entities;
  }

  /**
   * Implements \Drupal\Core\Ajax\CommandInterface::render().
   */
  public function render() {
    return [
      'command' => 'select_entities',
      'uuid' => $this->uuid,
      'entities' => $this->entities,
    ];
  }

}
