<?php

namespace Drupal\entity_browser;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Base implementation for widget validation plugins.
 */
abstract class WidgetValidationBase extends PluginBase implements WidgetValidationInterface, ContainerFactoryPluginInterface {

  /**
   * Plugin label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Typed Data Manager service.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, TypedDataManagerInterface $typed_data_manager) {
    $plugin_definition += [
      'constraint' => NULL,
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->typedDataManager = $typed_data_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('typed_data_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $entities, $options = []) {
    $plugin_definition = $this->getPluginDefinition();
    $data_definition = $this->getDataDefinition($plugin_definition['data_type'], $plugin_definition['constraint'], $options);
    return $this->validateDataDefinition($data_definition, $entities);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * Gets a data definition and optionally adds a constraint.
   *
   * @param string $data_type
   *   The data type plugin ID, for which a constraint should be added.
   * @param string $constraint_name
   *   The name of the constraint to add, i.e. its plugin id.
   * @param $options
   *   Array of options needed by the constraint validator.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   *   A data definition object for the given data type.
   */
  protected function getDataDefinition($data_type, $constraint_name = NULL, $options = []) {
    $data_definition = $this->typedDataManager->createDataDefinition($data_type);
    if ($constraint_name) {
      $data_definition->addConstraint($constraint_name, $options);
    }
    return $data_definition;
  }

  /**
   * Creates and validates instances of typed data for each Entity.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $data_definition
   *   The data definition generated from ::getDataDefinition().
   * @param array $entities
   *   An array of Entities to validate the definition against
   *
   * @return \Symfony\Component\Validator\ConstraintViolationListInterface
   *   A list of violations.
   */
  protected function validateDataDefinition(DataDefinitionInterface $data_definition, array $entities) {
    $violations = new ConstraintViolationList();
    foreach ($entities as $entity) {
      $validation_result = $this->typedDataManager->create($data_definition, $entity)->validate();
      $violations->addAll($validation_result);
    }

    return $violations;
  }
}
