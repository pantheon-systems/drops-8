<?php

namespace Drupal\crop;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages crop entity provider plugins.
 */
class EntityProviderManager extends DefaultPluginManager {

  /**
   * Constructs a new EntityProviderManager.
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
    parent::__construct('Plugin/Crop/EntityProvider', $namespaces, $module_handler, 'Drupal\crop\EntityProviderInterface', 'Drupal\crop\Annotation\CropEntityProvider');

    $this->alterInfo('crop_entity_provider_info');
    $this->setCacheBackend($cache_backend, 'crop_entity_provider_plugins');
  }

}
