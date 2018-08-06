<?php

namespace Drupal\pathauto;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Component\Plugin\FallbackPluginManagerInterface;

/**
 * Manages pathauto alias type plugins.
 */
class AliasTypeManager extends DefaultPluginManager implements FallbackPluginManagerInterface {

  /**
   * Constructs a new AliasType manager instance.
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
    parent::__construct('Plugin/pathauto/AliasType', $namespaces, $module_handler, 'Drupal\pathauto\AliasTypeInterface', 'Drupal\pathauto\Annotation\AliasType');
    $this->alterInfo('pathauto_alias_types');
    $this->setCacheBackend($cache_backend, 'pathauto_alias_types');
  }

  /**
   * Returns plugin definitions that support a given token type.
   *
   * @param string $type
   *   The type of token plugin must support to be useful.
   *
   * @return array
   *   Plugin definitions.
   */
  public function getPluginDefinitionByType($type) {
    $definitions = array_filter($this->getDefinitions(), function ($definition) use ($type) {
      if (!empty($definition['types']) && in_array($type, $definition['types'])) {
        return TRUE;
      }
      return FALSE;
    });
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = array()) {
    return 'broken';
  }

  /**
   * Gets the definition of all visible plugins for this type.
   *
   * @return array
   *   An array of plugin definitions (empty array if no definitions were
   *   found). Keys are plugin IDs.
   */
  public function getVisibleDefinitions() {
    $definitions = $this->getDefinitions();
    unset($definitions['broken']);
    return $definitions;
  }

}
