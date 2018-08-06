<?php

namespace Drupal\entity_browser;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages entity browser selection display plugins.
 */
class SelectionDisplayManager extends DefaultPluginManager {

  /**
   * Constructs a new SelectionDisplayManager.
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
    parent::__construct('Plugin/EntityBrowser/SelectionDisplay', $namespaces, $module_handler, 'Drupal\entity_browser\SelectionDisplayInterface', 'Drupal\entity_browser\Annotation\EntityBrowserSelectionDisplay');

    $this->alterInfo('entity_browser_selection_display_info');
    $this->setCacheBackend($cache_backend, 'entity_browser_selection_display_plugins');
  }

}
