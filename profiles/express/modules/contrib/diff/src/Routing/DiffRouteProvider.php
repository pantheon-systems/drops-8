<?php

namespace Drupal\diff\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Contains routes for diff functionality.
 */
class DiffRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = new RouteCollection();
    if ($route = $this->getDiffRoute($entity_type)) {
      $collection->add('entity.' . $entity_type->id() . '.revisions_diff', $route);
    }
    return $collection;
  }

  /**
   * Constructs the diff route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The diff route.
   */
  protected function getDiffRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('revisions-diff')) {
      $route = new Route($entity_type->getLinkTemplate('revisions-diff'));
      $route->addDefaults([
        '_controller' => '\Drupal\diff\Controller\PluginRevisionController::compareEntityRevisions',
        'filter' => 'split_fields',
      ]);
      $route->addRequirements([
        '_entity_access' => $entity_type->id() . '.view',
      ]);
      $route->setOption('parameters', [
        $entity_type->id() => ['type' => 'entity:' . $entity_type->id()],
        'left_revision' => ['type' => 'entity_revision:' . $entity_type->id()],
        'right_revision' => ['type' => 'entity_revision:' . $entity_type->id()],
      ]);
      return $route;
    }
  }

}
