<?php

namespace Drupal\pathauto\Plugin\migrate\source;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fetches pathauto patterns from the source database.
 *
 * @MigrateSource(
 *   id = "pathauto_pattern",
 *   source_module = "pathauto",
 * )
 */
class PathautoPattern extends DrupalSqlBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, EntityManagerInterface $entity_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_manager);
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Fetch all pattern variables whose value is not a serialized empty string.
    return $this->select('variable', 'v')
      ->fields('v', ['name', 'value'])
      ->condition('name', 'pathauto_%_pattern', 'LIKE')
      ->condition('value', 's:0:"";', '<>');
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['name']['type'] = 'string';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'name' => $this->t("The name of the pattern's variable."),
      'value' => $this->t("The value of the pattern's variable."),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $entity_definitions = $this->entityTypeManager->getDefinitions();
    $name = $row->getSourceProperty('name');
    // Pattern variables are made of pathauto_[entity type]_[bundle]_pattern.
    // First let's find a matching entity type from the variable name.
    foreach ($entity_definitions as $entity_type => $definition) {
      // Check if this is the default pattern for this entity type.
      // Otherwise, check if this is a pattern for a specific bundle.
      if ($name == 'pathauto_' . $entity_type . '_pattern') {
        // Set process values.
        $row->setSourceProperty('id', $entity_type);
        $row->setSourceProperty('label', (string) $definition->getLabel() . ' - default');
        $row->setSourceProperty('type', 'canonical_entities:' . $entity_type);
        $row->setSourceProperty('pattern', unserialize($row->getSourceProperty('value')));
        return parent::prepareRow($row);
      }
      elseif (strpos($name, 'pathauto_' . $entity_type . '_') === 0) {
        // Extract the bundle out of the variable name.
        preg_match('/^pathauto_' . $entity_type . '_([a-zA-z0-9_]+)_pattern$/', $name, $matches);
        $bundle = $matches[1];

        // Check that the bundle exists.
        $bundles = $this->entityManager->getBundleInfo($entity_type);
        if (!in_array($bundle, array_keys($bundles))) {
          // No matching bundle found in destination.
          return FALSE;
        }

        // Set process values.
        $row->setSourceProperty('id', $entity_type . '_' . $bundle);
        $row->setSourceProperty('label', (string) $definition->getLabel() . ' - ' . $bundles[$bundle]['label']);
        $row->setSourceProperty('type', 'canonical_entities:' . $entity_type);
        $row->setSourceProperty('pattern', unserialize($row->getSourceProperty('value')));

        $selection_criteria = [
          'id' => ($entity_type == 'node') ? 'node_type' : 'entity_bundle:' . $entity_type,
          'bundles' => [$bundle => $bundle],
          'negate' => FALSE,
          'context_mapping' => [$entity_type => $entity_type],
        ];
        $row->setSourceProperty('selection_criteria', [$selection_criteria]);
        return parent::prepareRow($row);
      }
    }

    return FALSE;
  }

}
