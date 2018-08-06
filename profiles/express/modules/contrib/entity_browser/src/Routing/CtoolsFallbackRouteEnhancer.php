<?php

namespace Drupal\entity_browser\Routing;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\Enhancer\RouteEnhancerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Enhances Entity browser edit/add form routes to display a message if ctools is missing.
 */
class CtoolsFallbackRouteEnhancer implements RouteEnhancerInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a CtoolsFallbackRouteEnhancer object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler service.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    if (!$this->moduleHandler->moduleExists('ctools')) {
      $defaults['_controller'] = '\Drupal\entity_browser\Controllers\CtoolsFallback::displayMessage';
    }

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return $route->hasDefault('_entity_wizard') && strpos($route->getDefault('_entity_wizard'), 'entity_browser.') === 0;
  }

}
