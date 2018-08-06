<?php

/**
 * @file
 * Contains \Drupal\auto_entitylabel\Routing\RouteSubscriber.
 */

namespace Drupal\auto_entitylabel\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for auto_entitylabel routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityTypeManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($route = $this->getEntityLabelRoute($entity_type)) {
        $collection->add("entity.$entity_type_id.auto_label", $route);
      }
    }
  }

  /**
   * Gets the Entity Auto Label route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getEntityLabelRoute(EntityTypeInterface $entity_type) {
    if ($route_load = $entity_type->getLinkTemplate('auto-label')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($route_load);
      $route
        ->addDefaults([
          '_form' => '\Drupal\auto_entitylabel\Form\AutoEntityLabelForm',
          '_title' => 'Automatic entity label',
        ])
        ->addRequirements([
          '_permission' => 'administer ' . $entity_type_id . ' labels',
        ])
        ->setOption('_admin_route', TRUE)
        ->setOption('parameters', [
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ]);
      return $route;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = array('onAlterRoutes', -100);
    return $events;
  }

}
