<?php

namespace Drupal\webform\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages webform handler plugins.
 *
 * @see hook_webform_handler_info_alter()
 * @see \Drupal\webform\Annotation\WebformHandler
 * @see \Drupal\webform\Plugin\WebformHandlerInterface
 * @see \Drupal\webform\Plugin\WebformHandlerBase
 * @see plugin_api
 */
class WebformHandlerManager extends DefaultPluginManager implements WebformHandlerManagerInterface {

  use CategorizingPluginManagerTrait {
    getSortedDefinitions as traitGetSortedDefinitions;
    getGroupedDefinitions as traitGetGroupedDefinitions;
  }

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a WebformHandlerManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    parent::__construct('Plugin/WebformHandler', $namespaces, $module_handler, 'Drupal\webform\Plugin\WebformHandlerInterface', 'Drupal\webform\Annotation\WebformHandler');
    $this->configFactory = $config_factory;

    $this->alterInfo('webform_handler_info');
    $this->setCacheBackend($cache_backend, 'webform_handler_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getSortedDefinitions(array $definitions = NULL) {
    // Sort the plugins first by category, then by label.
    $definitions = $this->traitGetSortedDefinitions($definitions);
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupedDefinitions(array $definitions = NULL) {
    $definitions = $this->traitGetGroupedDefinitions($definitions);
    // Do not display the 'broken' plugin in the UI.
    unset($definitions[$this->t('Broken')]['broken']);
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function removeExcludeDefinitions(array $definitions) {
    $definitions = isset($definitions) ? $definitions : $this->getDefinitions();

    // Exclude 'broken' handler.
    unset($definitions['broken']);

    $excluded = $this->configFactory->get('webform.settings')->get('handler.excluded_handlers');
    return $excluded ? array_diff_key($definitions, $excluded) : $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'broken';
  }

}
