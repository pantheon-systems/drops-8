<?php

namespace Drupal\entity_browser\Events;

use Symfony\Component\EventDispatcher\Event;

/**
 * Base implementation of entity browser events.
 */
class EventBase extends Event {

  /**
   * Entity browser id.
   *
   * @var string
   */
  protected $entityBrowserID;

  /**
   * Entity browser instance UUID.
   *
   * @var string
   */
  protected $instanceUUID;

  /**
   * Constructs a EntitySelectionEvent object.
   *
   * @param string $entity_browser_id
   *   Entity browser ID.
   * @param string $instance_uuid
   *   Entity browser instance UUID.
   */
  public function __construct($entity_browser_id, $instance_uuid) {
    $this->entityBrowserID = $entity_browser_id;
    $this->instanceUUID = $instance_uuid;
  }

  /**
   * Gets the entity browser ID:.
   *
   * @return string
   *   Entity browser ID.
   */
  public function getBrowserID() {
    return $this->entityBrowserID;
  }

  /**
   * Gets the entity browser instance UUID:.
   *
   * @return string
   *   Entity browser instance UUID.
   */
  public function getBrowserInstanceUUID() {
    return $this->instanceUUID;
  }

}
