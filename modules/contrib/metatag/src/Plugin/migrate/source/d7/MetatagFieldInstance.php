<?php

namespace Drupal\metatag\Plugin\migrate\source\d7;

use Drupal\Core\Entity\EntityTypeBundleInfo;
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
   * The EntityTypeBundleInfo for this entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_manager, $entity_type_bundle_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_manager);
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('state'),
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info')
    );
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

}
