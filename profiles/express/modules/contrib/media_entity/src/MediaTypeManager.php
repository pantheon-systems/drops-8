<?php

namespace Drupal\media_entity;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages media entity type plugins.
 */
class MediaTypeManager extends DefaultPluginManager {

  /**
   * Constructs a new MediaTypeManager.
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
    parent::__construct('Plugin/MediaEntity/Type', $namespaces, $module_handler, 'Drupal\media_entity\MediaTypeInterface', 'Drupal\media_entity\Annotation\MediaType');

    $this->alterInfo('media_entity_type_info');
    $this->setCacheBackend($cache_backend, 'media_entity_type_plugins');
  }

}
