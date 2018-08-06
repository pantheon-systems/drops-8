<?php

namespace Drupal\webform\Plugin;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for a webform handler.
 *
 * @see \Drupal\webform\Plugin\WebformHandlerInterface
 * @see \Drupal\webform\Plugin\WebformHandlerManager
 * @see \Drupal\webform\Plugin\WebformHandlerManagerInterface
 * @see plugin_api
 */
abstract class WebformHandlerBase extends PluginBase implements WebformHandlerInterface {

  /**
   * The webform.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform = NULL;

  /**
   * The webform handler ID.
   *
   * @var string
   */
  protected $handler_id;

  /**
   * The webform handler label.
   *
   * @var string
   */
  protected $label;

  /**
   * The webform handler status.
   *
   * @var bool
   */
  protected $status = 1;

  /**
   * The weight of the webform handler.
   *
   * @var int|string
   */
  protected $weight = '';

  /**
   * The webform handler's conditions.
   *
   * @var array
   */
  protected $conditions = [];

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  protected $submissionStorage;

  /**
   * The webform submission (server-side) conditions (#states) validator.
   *
   * @var \Drupal\webform\WebformSubmissionConditionsValidator
   */
  protected $conditionsValidator;

  /**
   * Constructs a WebformHandlerBase object.
   *
   * IMPORTANT:
   * Webform handlers are initialized and serialized when they are attached to a
   * webform. Make sure not include any services as a dependency injection
   * that directly connect to the database. This will prevent
   * "LogicException: The database connection is not serializable." exceptions
   * from being thrown when a form is serialized via an Ajax callaback and/or
   * form build.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\webform\WebformSubmissionConditionsValidatorInterface $conditions_validator
   *   The webform submission conditions (#states) validator.
   *
   * @see \Drupal\webform\Entity\Webform::getHandlers
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, WebformSubmissionConditionsValidatorInterface $conditions_validator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
    $this->loggerFactory = $logger_factory;
    $this->configFactory = $config_factory;
    $this->submissionStorage = $entity_type_manager->getStorage('webform_submission');
    $this->conditionsValidator = $conditions_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('webform_submission.conditions_validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setWebform(WebformInterface $webform) {
    $this->webform = $webform;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWebform() {
    return $this->webform;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      '#theme' => 'webform_handler_' . $this->pluginId . '_summary',
      '#settings' => $this->configuration,
      '#handler' => $this,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function description() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function cardinality() {
    return $this->pluginDefinition['cardinality'];
  }

  /**
   * {@inheritdoc}
   */
  public function supportsConditions() {
    return $this->pluginDefinition['conditions'];
  }

  /**
   * {@inheritdoc}
   */
  public function getHandlerId() {
    return $this->handler_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setHandlerId($handler_id) {
    $this->handler_id = $handler_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label ?: $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->status = $status;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function setConditions(array $conditions) {
    $this->conditions = $conditions;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConditions() {
    return $this->conditions;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function isExcluded() {
    return $this->configFactory->get('webform.settings')->get('handler.excluded_handlers.' . $this->pluginDefinition['id']) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->status ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isDisabled() {
    return !$this->isEnabled();
  }

  /**
   * {@inheritdoc}
   */
  public function isSubmissionOptional() {
    return ($this->pluginDefinition['submission'] === self::SUBMISSION_OPTIONAL);
  }

  /**
   * {@inheritdoc}
   */
  public function isSubmissionRequired() {
    return ($this->pluginDefinition['submission'] === self::SUBMISSION_REQUIRED);
  }

  /**
   * {@inheritdoc}
   */
  public function checkConditions(WebformSubmissionInterface $webform_submission) {
    // Return TRUE if conditions are disabled for the handler.
    if (!$this->supportsConditions()) {
      return TRUE;
    }

    $conditions = $this->getConditions();

    // Return TRUE if no conditions are defined.
    if (empty($conditions)) {
      return TRUE;
    }

    $state = key($conditions);
    $conditions = $conditions[$state];
    $result = $this->conditionsValidator->validateConditions($conditions, $webform_submission);

    // Negate result for 'disabled' state.
    return ($state === 'disabled') ? !$result : $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'id' => $this->getPluginId(),
      'label' => $this->getLabel(),
      'handler_id' => $this->getHandlerId(),
      'status' => $this->getStatus(),
      'conditions' => $this->getConditions(),
      'weight' => $this->getWeight(),
      'settings' => $this->configuration,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $configuration += [
      'handler_id' => '',
      'label' => '',
      'status' => 1,
      'conditions' => [],
      'weight' => '',
      'settings' => [],
    ];
    $this->configuration = $configuration['settings'] + $this->defaultConfiguration();
    $this->handler_id = $configuration['handler_id'];
    $this->label = $configuration['label'];
    $this->status = $configuration['status'];
    $this->conditions = $configuration['conditions'];
    $this->weight = $configuration['weight'];
    return $this;
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
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Apply submitted form state to configuration.
   *
   * This method can used to update configuration when the configuration form
   * is being rebuilt during an #ajax callback.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function applyFormStateToConfiguration(FormStateInterface $form_state) {
    $values = $form_state->getValues();
    foreach ($values as $key => $value) {
      if (isset($this->configuration[$key])) {
        $this->configuration[$key] = $value;
      }
    }
  }

  /****************************************************************************/
  // Webform methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function alterElements(array &$elements, WebformInterface $webform) {}

  /****************************************************************************/
  // Submission form methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {}

  /****************************************************************************/
  // Submission methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function preCreate(array $values) {}

  /**
   * {@inheritdoc}
   */
  public function postCreate(WebformSubmissionInterface $webform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function postLoad(WebformSubmissionInterface $webform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function preDelete(WebformSubmissionInterface $webform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function postDelete(WebformSubmissionInterface $webform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function preSave(WebformSubmissionInterface $webform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {}

  /****************************************************************************/
  // Preprocessing methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function preprocessConfirmation(array &$variables) {}

  /****************************************************************************/
  // Handler methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function createHandler() {}

  /**
   * {@inheritdoc}
   */
  public function updateHandler() {}

  /**
   * {@inheritdoc}
   */
  public function deleteHandler() {}

  /****************************************************************************/
  // Element methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function createElement($key, array $element) {}

  /**
   * {@inheritdoc}
   */
  public function updateElement($key, array $element, array $original_element) {}

  /**
   * {@inheritdoc}
   */
  public function deleteElement($key, array $element) {}

  /****************************************************************************/
  // Loggin methods.
  /****************************************************************************/

  /**
   * Get webform logger.
   *
   * @return \Drupal\Core\Logger\LoggerChannelInterface
   *   Webform logger
   */
  protected function getLogger() {
    return $this->loggerFactory->get('webform');
  }

  /**
   * Log a webform handler's submission operation.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param string $operation
   *   The operation to be logged.
   * @param string $message
   *   The message to be logged.
   * @param array $data
   *   The data to be saved with log record.
   */
  protected function log(WebformSubmissionInterface $webform_submission, $operation, $message = '', array $data = []) {
    if ($webform_submission->getWebform()->hasSubmissionLog()) {
      $this->submissionStorage->log($webform_submission, [
        'handler_id' => $this->getHandlerId(),
        'operation' => $operation,
        'message' => $message,
        'data' => $data,
      ]);
    }
  }

}
