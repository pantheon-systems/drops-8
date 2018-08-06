<?php

/**
 * @file
 * Contains \Drupal\linkit\MatcherManager.
 */

namespace Drupal\linkit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages matchers.
 */
class MatcherManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Linkit/Matcher', $namespaces, $module_handler, 'Drupal\linkit\MatcherInterface', 'Drupal\linkit\Annotation\Matcher');

    $this->alterInfo('linkit_matcher');
    $this->setCacheBackend($cache_backend, 'linkit_matchers');
  }

}
