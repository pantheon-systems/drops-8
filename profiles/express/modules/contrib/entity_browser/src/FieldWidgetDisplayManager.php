<?php

namespace Drupal\entity_browser;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages entity browser field widget display plugins.
 */
class FieldWidgetDisplayManager extends DefaultPluginManager {

  /**
   * Constructs a new FieldWidgetDisplayManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/EntityBrowser/FieldWidgetDisplay', $namespaces, $module_handler, 'Drupal\entity_browser\FieldWidgetDisplayInterface', 'Drupal\entity_browser\Annotation\EntityBrowserFieldWidgetDisplay');

    $this->alterInfo('entity_browser_field_widget_display_info');
    $this->setCacheBackend($cache_backend, 'entity_browser_field_widget_display_plugins');
  }

}
