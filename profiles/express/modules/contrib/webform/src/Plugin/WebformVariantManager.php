<?php

namespace Drupal\webform\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages webform variant plugins.
 *
 * @see hook_webform_variant_info_alter()
 * @see \Drupal\webform\Annotation\WebformVariant
 * @see \Drupal\webform\Plugin\WebformVariantInterface
 * @see \Drupal\webform\Plugin\WebformVariantBase
 * @see plugin_api
 */
class WebformVariantManager extends DefaultPluginManager implements WebformVariantManagerInterface {

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
   * Constructs a WebformVariantManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module variant.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    parent::__construct('Plugin/WebformVariant', $namespaces, $module_handler, 'Drupal\webform\Plugin\WebformVariantInterface', 'Drupal\webform\Annotation\WebformVariant');
    $this->configFactory = $config_factory;

    $this->alterInfo('webform_variant_info');
    $this->setCacheBackend($cache_backend, 'webform_variant_plugins');
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

    // Exclude 'broken' variant.
    unset($definitions['broken']);

    $excluded = $this->configFactory->get('webform.settings')->get('variant.excluded_variants') ?: [];
    return $excluded ? array_diff_key($definitions, $excluded) : $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'broken';
  }

}
