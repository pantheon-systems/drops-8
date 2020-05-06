<?php

namespace Drupal\webform\Plugin;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Webform source entity plugin manager.
 */
class WebformSourceEntityManager extends DefaultPluginManager implements WebformSourceEntityManagerInterface {

  /**
   * Constructs a WebformSourceEntityManager object.
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
    parent::__construct('Plugin/WebformSourceEntity', $namespaces, $module_handler, 'Drupal\webform\Plugin\WebformSourceEntityInterface', 'Drupal\webform\Annotation\WebformSourceEntity');
    $this->alterInfo('webform_source_entity_info');
    $this->setCacheBackend($cache_backend, 'webform_source_entity_info_plugins');
  }

  /**
   * {@inheritdoc}
   */
  protected function alterDefinitions(&$definitions) {
    // Unset elements that are missing target element or dependencies.
    foreach ($definitions as $element_key => $element_definition) {
      // Check element's (module) dependencies exist.
      foreach ($element_definition['dependencies'] as $dependency) {
        if (!$this->moduleHandler->moduleExists($dependency)) {
          unset($definitions[$element_key]);
          continue;
        }
      }
    }

    // Additionally sort by weight so we always have them sorted in proper
    // order.
    uasort($definitions, [SortArray::class, 'sortByWeightElement']);

    parent::alterDefinitions($definitions);
  }

  /**
   * Get the main source entity. Applies to only paragraphs.
   *
   * @param \Drupal\Core\Entity\EntityInterface $source_entity
   *   A source entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The main source entity.
   *
   * @see \Drupal\webform\Plugin\Field\FieldFormatter\WebformEntityReferenceEntityFormatter::viewElements
   * @see \Drupal\webform\Plugin\WebformSourceEntity\QueryStringWebformSourceEntity::getSourceEntity
   */
  public static function getMainSourceEntity(EntityInterface $source_entity) {
    if (\Drupal::moduleHandler()->moduleExists('paragraphs')) {
      while ($source_entity instanceof \Drupal\paragraphs\Entity\Paragraph) {
        $source_entity = $source_entity->getParentEntity();
      }
    }
    return $source_entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceEntity($ignored_types = []) {
    if (!is_array($ignored_types)) {
      $ignored_types = [$ignored_types];
    }

    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      /** @var WebformSourceEntityInterface $instance */
      $instance = $this->createInstance($plugin_id);
      $source_entity = $instance->getSourceEntity($ignored_types);
      if ($source_entity) {
        return $source_entity;
      }
    }

    return NULL;
  }

}
