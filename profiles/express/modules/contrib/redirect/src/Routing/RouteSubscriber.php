<?php

namespace Drupal\redirect\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Modify core routes to support redirect.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('image.style_public')) {
      $route->setDefault('_disable_route_normalizer', TRUE);
    }
    if ($route = $collection->get('image.style_private')) {
      $route->setDefault('_disable_route_normalizer', TRUE);
    }
    if ($route = $collection->get('system.files')) {
      $route->setDefault('_disable_route_normalizer', TRUE);
    }
  }

}
