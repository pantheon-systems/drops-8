<?php

namespace Drupal\views_slideshow;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manager for Views Slideshow widget type plugins.
 */
class ViewsSlideshowWidgetTypePluginManager extends DefaultPluginManager {

  /**
   * Constructs a new ViewsSlideshowWidgetTypePluginManager.
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
    parent::__construct('Plugin/ViewsSlideshowWidgetType', $namespaces, $module_handler, 'Drupal\views_slideshow\ViewsSlideshowWidgetTypeInterface', 'Drupal\views_slideshow\Annotation\ViewsSlideshowWidgetType');
    $this->alterInfo('views_slideshow_widget_type_info');
    $this->setCacheBackend($cache_backend, 'views_slideshow_widget_type');
  }

}
