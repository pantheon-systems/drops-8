<?php

namespace Drupal\webform\Plugin;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;
use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Collects available webform elements.
 */
interface WebformElementManagerInterface extends PluginManagerInterface, CachedDiscoveryInterface, FallbackPluginManagerInterface, CategorizingPluginManagerInterface {

  /**
   * Get all available webform element plugin instances.
   *
   * @return \Drupal\webform\Plugin\WebformElementInterface[]
   *   An array of all available webform element plugin instances.
   */
  public function getInstances();

  /**
   * Invoke a method for specific FAPI element.
   *
   * @param string $method
   *   The method name.
   * @param array $element
   *   An associative array containing an element with a #type property.
   * @param mixed $context1
   *   (optional) An additional variable that is passed by reference.
   * @param mixed $context2
   *   (optional) An additional variable that is passed by reference. If more
   *   context needs to be provided to implementations, then this should be an
   *   associative array as described above.
   *
   * @return mixed|null
   *   Return result of the invoked method.  NULL will be returned if the
   *   element and/or method name does not exist.
   */
  public function invokeMethod($method, array &$element, &$context1 = NULL, &$context2 = NULL);

  /**
   * Is an element's plugin id.
   *
   * @param array $element
   *   A element.
   *
   * @return string
   *   An element's $type has a corresponding plugin id, else
   *   fallback 'element' plugin id.
   */
  public function getElementPluginId(array $element);

  /**
   * Get a webform element plugin instance for an element.
   *
   * @param array $element
   *   An associative array containing an element with a #type property.
   *
   * @return \Drupal\webform\Plugin\WebformElementInterface
   *   A webform element plugin instance
   */
  public function getElementInstance(array $element);

  /**
   * Gets sorted plugin definitions.
   *
   * @param array[]|null $definitions
   *   (optional) The plugin definitions to sort. If omitted, all plugin
   *   definitions are used.
   * @param string $sort_by
   *   The property to sort plugin definitions by. Only 'label' and 'category'
   *   are supported. Defaults to label.
   *
   * @return array[]
   *   An array of plugin definitions, sorted by category and label.
   */
  public function getSortedDefinitions(array $definitions = NULL, $sort_by = 'label');

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
   * Get all translatable properties from all elements.
   *
   * @return array
   *   An array of translatable properties.
   */
  public function getTranslatableProperties();

  /**
   * Get all properties for all elements.
   *
   * @return array
   *   An array of all properties.
   */
  public function getAllProperties();

}
