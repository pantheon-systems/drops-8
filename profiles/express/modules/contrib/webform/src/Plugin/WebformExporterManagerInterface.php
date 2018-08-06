<?php

namespace Drupal\webform\Plugin;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Component\Plugin\CategorizingPluginManagerInterface;

/**
 * Collects available results exporters.
 */
interface WebformExporterManagerInterface extends PluginManagerInterface, CachedDiscoveryInterface, FallbackPluginManagerInterface, CategorizingPluginManagerInterface {

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

  /**
   * Get all available webform element plugin instances.
   *
   * @param array $configuration
   *   Export configuration (aka export options).
   *
   * @return \Drupal\webform\Plugin\WebformExporterInterface[]
   *   An array of all available webform exporter plugin instances.
   */
  public function getInstances(array $configuration = []);

  /**
   * Get exporter plugins as options.
   *
   * @return array
   *   An associative array of options keyed by plugin id.
   */
  public function getOptions();

}
