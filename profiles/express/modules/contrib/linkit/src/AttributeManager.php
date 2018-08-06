<?php

/**
 * @file
 * Contains \Drupal\linkit\AttributeManager.
 */

namespace Drupal\linkit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages attributes.
 */
class AttributeManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Linkit/Attribute', $namespaces, $module_handler, 'Drupal\linkit\AttributeInterface', 'Drupal\linkit\Annotation\Attribute');

    $this->alterInfo('linkit_attribute');
    $this->setCacheBackend($cache_backend, 'linkit_attributes');
  }

}
