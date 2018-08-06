<?php

namespace Drupal\video_filter;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * VideoFilter plugin manager.
 *
 * @package Drupal\video_filter
 */
class VideoFilterManager extends DefaultPluginManager {

  /**
   * Constructs an VideoFilterManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/VideoFilter', $namespaces, $module_handler, 'Drupal\video_filter\VideoFilterInterface', 'Drupal\video_filter\Annotation\VideoFilter');
    $this->alterInfo('video_filter_info');
    $this->setCacheBackend($cache_backend, 'video_filter');
  }

}
