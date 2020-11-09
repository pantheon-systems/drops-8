<?php

namespace Drupal\metatag\Plugin\migrate\source\d7;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drupal 7 Metatag field instances.
 *
 * @MigrateSource(
 *   id = "d7_metatag_field_instance",
 *   source_module = "metatag"
 * )
 */
class MetatagFieldInstance extends DrupalSqlBase {

  /**
   * The entity type bundle service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    /** @var static $source */
    $source = parent::create($container, $configuration, $plugin_id, $plugin_definition, $migration);
    $source->setEntityTypeBundleInfo($container->get('entity_type.bundle.info'));
    return $source;
  }

  /**
   * Sets the entity type bundle info service.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function setEntityTypeBundleInfo(EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('metatag', 'm')
      ->fields('m', ['entity_type'])
      ->groupBy('entity_type');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'entity_type' => $this->t('Entity type'),
      'bundle' => $this->t('Bundle'),
    ];
  }

  /**
   * Returns each entity_type/bundle pair.
   */
  public function initializeIterator() {
    $bundles = [];
    foreach (parent::initializeIterator() as $instance) {
      $bundle_info = $this->entityTypeBundleInfo
        ->getBundleInfo($instance['entity_type']);
      foreach (array_keys($bundle_info) as $bundle) {
        $bundles[] = [
          'entity_type' => $instance['entity_type'],
          'bundle' => $bundle,
        ];
      }
    }
    return new \ArrayIterator($bundles);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['entity_type']['type'] = 'string';
    $ids['bundle']['type'] = 'string';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function count($refresh = FALSE) {
    return $this->initializeIterator()->count();
  }

}
