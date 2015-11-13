<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\destination\Entity.
 */

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Entity\DependencyTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @MigrateDestination(
 *   id = "entity",
 *   deriver = "Drupal\migrate\Plugin\Derivative\MigrateEntity"
 * )
 */
abstract class Entity extends DestinationBase implements ContainerFactoryPluginInterface, DependentPluginInterface {
  use DependencyTrait;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The list of the bundles of this entity type.
   *
   * @var array
   */
  protected $bundles;

  /**
   * Construct a new entity.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param MigrationInterface $migration
   *   The migration.
   * @param EntityStorageInterface $storage
   *   The storage for this entity type.
   * @param array $bundles
   *   The list of bundles this entity type has.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityStorageInterface $storage, array $bundles) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->storage = $storage;
    $this->bundles = $bundles;
    $this->supportsRollback = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    $entity_type_id = static::getEntityTypeId($plugin_id);
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity.manager')->getStorage($entity_type_id),
      array_keys($container->get('entity.manager')->getBundleInfo($entity_type_id))
    );
  }

  /**
   * Finds the entity type from configuration or plugin id.
   *
   * @param string $plugin_id
   *   The plugin id.
   *
   * @return string
   *   The entity type.
   */
  protected static function getEntityTypeId($plugin_id) {
    // Remove "entity:"
    return substr($plugin_id, 7);
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    // TODO: Implement fields() method.
  }

  /**
   * Creates or loads an entity.
   *
   * @param \Drupal\migrate\Row $row
   *   The row object.
   * @param array $old_destination_id_values
   *   The old destination ids.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity we're importing into.
   */
  protected function getEntity(Row $row, array $old_destination_id_values) {
    $entity_id = $old_destination_id_values ? reset($old_destination_id_values) : $this->getEntityId($row);
    if (!empty($entity_id) && ($entity = $this->storage->load($entity_id))) {
      $this->updateEntity($entity, $row);
    }
    else {
      // Stubs might need some required fields filled in.
      if ($row->isStub()) {
        $this->processStubRow($row);
      }
      $entity = $this->storage->create($row->getDestination());
      $entity->enforceIsNew();
    }
    return $entity;
  }

  /**
   * Get the entity id of the row.
   *
   * @param \Drupal\migrate\Row $row
   *   The row of data.
   * @return string
   *   The entity id for the row we're importing.
   */
  protected function getEntityId(Row $row) {
    return $row->getDestinationProperty($this->getKey('id'));
  }

  /**
   * Returns a specific entity key.
   *
   * @param string $key
   *   The name of the entity key to return.
   *
   * @return string|bool
   *   The entity key, or FALSE if it does not exist.
   *
   * @see \Drupal\Core\Entity\EntityTypeInterface::getKeys()
   */
  protected function getKey($key) {
    return $this->storage->getEntityType()->getKey($key);
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    // Delete the specified entity from Drupal if it exists.
    $entity = $this->storage->load(reset($destination_identifier));
    if ($entity) {
      $entity->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $this->addDependency('module', $this->storage->getEntityType()->getProvider());
    return $this->dependencies;
  }

}
