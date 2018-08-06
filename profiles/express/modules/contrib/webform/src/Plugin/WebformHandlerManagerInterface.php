<?php

namespace Drupal\webform\Plugin;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Component\Plugin\CategorizingPluginManagerInterface;

/**
 * Collects available webform handlers.
 */
interface WebformHandlerManagerInterface extends PluginManagerInterface, CachedDiscoveryInterface, FallbackPluginManagerInterface, CategorizingPluginManagerInterface {

  /**
   * Remove excluded plugin definitions.
   *
   * @param array $definitions
   *   The plugin definitions to filter.
   *
   * @return array
   *   An array of plugin definitions with excluded plugins removed.
   */
  public function removeExcludeDefinitions(array $definitions);

}
