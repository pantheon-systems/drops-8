<?php

namespace Drupal\ctools\Routing\Enhancer;

use Drupal\Core\Routing\EnhancerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Sets the request format onto the request object.
 */
class WizardEnhancer implements EnhancerInterface {

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    $route = $defaults[RouteObjectInterface::ROUTE_OBJECT];
    if (!$this->isApplicable($route)) {
      return $defaults;
    }

    if (!empty($defaults['_wizard'])) {
      $defaults['_controller'] = 'ctools.wizard.form:getContentResult';
    }
    if (!empty($defaults['_entity_wizard'])) {
      $defaults['_controller'] = 'ctools.wizard.entity.form:getContentResult';
    }

    return $defaults;
  }

  /**
   * Returns if current route use ctools default parameters.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check.
   *
   * @return bool
   *   TRUE if the route use one of ctools route default parameters or FALSE.
   */
  public function isApplicable(Route $route) {
    return !$route->hasDefault('_controller') && ($route->hasDefault('_wizard') || $route->hasDefault('_entity_wizard'));
  }

}
