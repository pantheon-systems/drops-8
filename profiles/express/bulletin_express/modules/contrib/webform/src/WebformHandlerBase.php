<?php

namespace Drupal\webform;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for a webform handler.
 *
 * @see \Drupal\webform\WebformHandlerInterface
 * @see \Drupal\webform\WebformHandlerManager
 * @see \Drupal\webform\WebformHandlerManagerInterface
 * @see plugin_api
 */
abstract class WebformHandlerBase extends PluginBase implements WebformHandlerInterface {

  /**
   * The webform .
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
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  protected $submissionStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
    $this->logger = $logger;
    $this->submissionStorage = $entity_type_manager->getStorage('webform_submission');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('webform'),
      $container->get('entity_type.manager')
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
      '#handler' => [
        'id' => $this->pluginDefinition['id'],
        'handler_id' => $this->getHandlerId(),
        'label' => $this->label(),
        'description' => $this->pluginDefinition['description'],
      ],
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
  public function getConfiguration() {
    return [
      'id' => $this->getPluginId(),
      'label' => $this->getLabel(),
      'handler_id' => $this->getHandlerId(),
      'status' => $this->getStatus(),
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
      'weight' => '',
      'settings' => [],
    ];
    $this->configuration = $configuration['settings'] + $this->defaultConfiguration();
    $this->handler_id = $configuration['handler_id'];
    $this->label = $configuration['label'];
    $this->weight = $configuration['weight'];
    $this->status = $configuration['status'];
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
   * Apply submitted form state settings to configuration.
   *
   * This method can used to update configuration when the configuration form
   * is being rebuilt during an #ajax callback.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function applyFormStateSettingsToConfiguration(FormStateInterface $form_state) {
    $settings = $form_state->getValue('settings') ?: [];
    foreach ($settings as $setting_key => $setting_value) {
      if (isset($this->configuration[$setting_key])) {
        $this->configuration[$setting_key] = $setting_value;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterElements(array &$elements, WebformInterface $webform) {}

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
