<?php

namespace Drupal\webform\Plugin;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\webform\Utility\WebformElementHelper;
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
   * The webform submission.
   *
   * @var \Drupal\webform\WebformSubmissionInterface
   */
  protected $webformSubmission = NULL;

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
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * Constructs a WebformHandlerBase object.
   *
   * IMPORTANT:
   * Webform handlers are initialized and serialized when they are attached to a
   * webform. Make sure not include any services as a dependency injection
   * that directly connect to the database. This will prevent
   * "LogicException: The database connection is not serializable." exceptions
   * from being thrown when a form is serialized via an Ajax callback and/or
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
    $this->loggerFactory = $logger_factory;
    $this->configFactory = $config_factory;
    $this->submissionStorage = $entity_type_manager->getStorage('webform_submission');
    $this->conditionsValidator = $conditions_validator;

    // @todo Webform 8.x-6.x: Properly inject the token manager.
    // @todo Webform 8.x-6.x: Update handlers that injects the token manager.
    $this->tokenManager = \Drupal::service('webform.token_manager');

    $this->setConfiguration($configuration);
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
  public function setWebformSubmission(WebformSubmissionInterface $webform_submission = NULL) {
    $this->webformSubmission = $webform_submission;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWebformSubmission() {
    return $this->webformSubmission;
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
  public function supportsTokens() {
    return $this->pluginDefinition['tokens'];
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
  public function enable() {
    return $this->setStatus(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function disable() {
    return $this->setStatus(FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function isExcluded() {
    return $this->configFactory->get('webform.settings')
      ->get('handler.excluded_handlers.' . $this->pluginDefinition['id']) ? TRUE : FALSE;
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
  public function isApplicable(WebformInterface $webform) {
    return TRUE;
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
  public function hasAnonymousSubmissionTracking() {
    return FALSE;
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

    // Get conditions.
    $state = key($conditions);
    $conditions = $conditions[$state];

    // Replace tokens in conditions.
    $conditions = $this->replaceTokens($conditions, $webform_submission);

    // Validation conditions.
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
    $default_configuration = $this->defaultConfiguration();
    foreach ($values as $key => $value) {
      if (array_key_exists($key, $this->configuration)) {
        if (is_bool($default_configuration[$key])) {
          $this->configuration[$key] = (boolean) $value;
        }
        elseif (is_int($default_configuration[$key])) {
          $this->configuration[$key] = (integer) $value;
        }
        else {
          $this->configuration[$key] = $value;
        }
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

  /**
   * {@inheritdoc}
   */
  public function alterElement(array &$element, FormStateInterface $form_state, array $context) {}

  /****************************************************************************/
  // Webform submission methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function overrideSettings(array &$settings, WebformSubmissionInterface $webform_submission) {}

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
  public function preCreate(array &$values) {}

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
  public function access(WebformSubmissionInterface $webform_submission, $operation, AccountInterface $account = NULL) {
    return AccessResult::neutral();
  }

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
  public function accessElement(array &$element, $operation, AccountInterface $account = NULL) {
    return AccessResult::neutral();
  }

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
  // Form helper methods.
  /****************************************************************************/

  /**
   * Set configuration settings parents.
   *
   * This helper method looks looks for the handler default configuration keys
   * within a form and set a matching element's #parents property to
   * ['settings', '{element_key}']
   *
   * @param array $elements
   *   An array of form elements.
   *
   * @return array
   *   Form element with #parents set.
   */
  protected function setSettingsParents(array &$elements) {
    return $this->setSettingsParentsRecursively($elements);
  }

  /**
   * Set configuration settings parents.
   *
   * This helper method looks looks for the handler default configuration keys
   * within a form and set a matching element's #parents property to
   * ['settings', '{element_key}']
   *
   * @param array $elements
   *   An array of form elements.
   *
   * @return array
   *   Form element with #parents set.
   */
  protected function setSettingsParentsRecursively(array &$elements) {
    $default_configuration = $this->defaultConfiguration();
    foreach ($elements as $element_key => &$element) {
      // Only a form element can have #parents.
      if (!WebformElementHelper::isElement($element, $element_key)) {
        continue;
      }

      // If the element has #parents property assume that it has also been
      // defined for all sub-elements.
      if (isset($element['#parents'])) {
        continue;
      }

      // Only set #parents when #element has…
      // - Default configuration.
      // - Is an input.
      // - #default_value or #value (aka input).
      // - Not a container with children.
      if (array_key_exists($element_key, $default_configuration)
        && isset($element['#type'])
        && !WebformElementHelper::hasChildren($element)) {
        $element['#parents'] = ['settings', $element_key];
      }
      else {
        $this->setSettingsParentsRecursively($element);
      }
    }
    return $elements;
  }

  /****************************************************************************/
  // Token methods.
  /****************************************************************************/

  /**
   * Replace tokens in text with no render context.
   *
   * @param string|array $text
   *   A string of text that may contain tokens.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   A Webform or Webform submission entity.
   * @param array $data
   *   (optional) An array of keyed objects.
   * @param array $options
   *   (optional) A keyed array of settings and flags to control the token
   *   replacement process. Supported options are:
   *   - langcode: A language code to be used when generating locale-sensitive
   *     tokens.
   *   - callback: A callback function that will be used to post-process the
   *     array of token replacements after they are generated.
   *   - clear: A boolean flag indicating that tokens should be removed from the
   *     final text if no replacement value can be generated.
   *
   * @return string|array
   *   Text or array with tokens replaced.
   */
  protected function replaceTokens($text, EntityInterface $entity = NULL, array $data = [], array $options = []) {
    return $this->tokenManager->replaceNoRenderContext($text, $entity, $data, $options);
  }

  /**
   * Build token tree element.
   *
   * @param array $token_types
   *   (optional) An array containing token types that should be shown in the tree.
   * @param string $description
   *   (optional) Description to appear after the token tree link.
   *
   * @return array
   *   A render array containing a token tree link wrapped in a div.
   */
  protected function buildTokenTreeElement(array $token_types = ['webform', 'webform_submission'], $description = NULL) {
    return $this->tokenManager->buildTreeElement($token_types, $description);
  }

  /**
   * Validate form that should have tokens in it.
   *
   * @param array $form
   *   A form.
   * @param array $token_types
   *   An array containing token types that should be validated.
   *
   * @see token_element_validate()
   */
  protected function elementTokenValidate(array &$form, array $token_types = ['webform', 'webform_submission', 'webform_handler']) {
    return $this->tokenManager->elementValidate($form, $token_types);
  }

  /****************************************************************************/
  // Logging methods.
  /****************************************************************************/

  /**
   * Get webform or webform_submission logger.
   *
   * @param string $channel
   *   The logger channel. Defaults to 'webform'.
   *
   * @return \Drupal\Core\Logger\LoggerChannelInterface
   *   Webform logger
   */
  protected function getLogger($channel = 'webform') {
    return $this->loggerFactory->get($channel);
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
   *
   * @deprecated Instead call the 'webform_submission' logger channel directly.
   *
   *  $message = 'Some message with an %argument.'
   *  $context = [
   *    '%argument' => 'Some value'
   *    'link' => $webform_submission->toLink($this->t('Edit'), 'edit-form')->toString(),
   *    'webform_submission' => $webform_submission,
   *    'handler_id' => NULL,
   *    'data' => [],
   *  ];
   *  \Drupal::logger('webform_submission')->notice($message, $context);
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
