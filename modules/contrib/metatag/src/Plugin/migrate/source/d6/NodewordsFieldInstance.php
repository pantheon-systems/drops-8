<?php

namespace Drupal\metatag\Plugin\migrate\source\d6;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drupal 6 Nodewords field instances.
 *
 * @MigrateSource(
 *   id = "d6_nodewords_field_instance",
 *   source_module = "nodewords"
 * )
 */
class NodewordsFieldInstance extends DrupalSqlBase {

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
    return $this->select('nodewords', 'n')
      ->fields('n', ['type'])
      ->groupBy('type');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'type' => $this->t('Configuration type'),
      'bundle' => $this->t('Bundle'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $bundles = [];
    foreach (parent::initializeIterator() as $instance) {
      $entity_type = NULL;
      switch ($instance['type']) {
        case 5:
          // define('NODEWORDS_TYPE_NODE',       5);
          $entity_type = 'node';
          break;

        case 6:
          // define('NODEWORDS_TYPE_TERM',       6);
          $entity_type = 'taxonomy_term';
          break;

        case 8:
          // define('NODEWORDS_TYPE_USER',       8);
          $entity_type = 'user';
          break;

        default:
          continue 2;
      }
      $bundle_info = $this->entityTypeBundleInfo
        ->getBundleInfo($entity_type);
      foreach (array_keys($bundle_info) as $bundle) {
        $bundles[] = [
          'entity_type' => $entity_type,
          'bundle' => $bundle,
          'type' => $instance['type'],
        ];
      }
    }
    return new \ArrayIterator($bundles);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['type']['type'] = 'integer';
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
