<?php

namespace Drupal\webform\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Adds the _admin_route option to webform routes.
 */
class WebformRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Set admin route for webform admin routes.
    foreach ($collection->all() as $route) {
      if (!$route->hasOption('_admin_route') && (
          strpos($route->getPath(), '/admin/structure/webform/') === 0
          || strpos($route->getPath(), '/webform/results/') !== FALSE
        )) {
        $route->setOption('_admin_route', TRUE);
      }
    }
  }

}
