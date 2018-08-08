<?php

/**
 * @file
 * Listens for logout route and redirects request to "/logout" path.
 */

namespace Drupal\cu_saml\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class LogoutSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Change path '/user/logout' to '/logout'.
    if ($route = $collection->get('user.logout')) {
      $route->setPath('/logout');
    }
  }

}
