<?php

namespace Drupal\webform\Plugin\WebformHandler;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform submission action handler.
 *
 * @WebformHandler(
 *   id = "action",
 *   label = @Translation("Action"),
 *   category = @Translation("Action"),
 *   description = @Translation("Trigger an action on a submission."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 *   tokens = TRUE,
 * )
 */
class ActionWebformHandler extends WebformHandlerBase {

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, WebformSubmissionConditionsValidatorInterface $conditions_validator, WebformTokenManagerInterface $token_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger_factory, $config_factory, $entity_type_manager, $conditions_validator);
    $this->tokenManager = $token_manager;
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
      $container->get('webform_submission.conditions_validator'),
      $container->get('webform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $configuration = $this->getConfiguration();
    $settings = $configuration['settings'];

    // Get state labels.
    $states = [
      WebformSubmissionInterface::STATE_DRAFT_CREATED => $this->t('Draft created'),
      WebformSubmissionInterface::STATE_DRAFT_UPDATED => $this->t('Draft updated'),
      WebformSubmissionInterface::STATE_CONVERTED => $this->t('Converted'),
      WebformSubmissionInterface::STATE_COMPLETED => $this->t('Completed'),
      WebformSubmissionInterface::STATE_UPDATED => $this->t('Updated'),
      WebformSubmissionInterface::STATE_LOCKED => $this->t('Locked'),
    ];
    $settings['states'] = array_intersect_key($states, array_combine($settings['states'], $settings['states']));

    // Get message type.
    $message_types = [
      'status' => $this->t('Status'),
      'error' => $this->t('Error'),
      'warning' => $this->t('Warning'),
      'info' => $this->t('Info'),
    ];
    $settings['message'] = $settings['message'] ? WebformHtmlEditor::checkMarkup($settings['message']) : NULL;
    $settings['message_type'] = $message_types[$settings['message_type']];

    // Get data element keys.
    $data = Yaml::decode($settings['data']) ?: [];
    $settings['data'] = array_keys($data);

    return [
      '#settings' => $settings,
    ] + parent::getSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'states' => [WebformSubmissionInterface::STATE_COMPLETED],
      'notes' => '',
      'sticky' => NULL,
      'locked' => NULL,
      'data' => '',
      'message' => '',
      'message_type' => 'status',
      'debug' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $results_disabled = $this->getWebform()->getSetting('results_disabled');

    $form['trigger'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Trigger'),
    ];
    $form['trigger']['states'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Execute'),
      '#options' => [
        WebformSubmissionInterface::STATE_DRAFT_CREATED => $this->t('…when <b>draft is created</b>.'),
        WebformSubmissionInterface::STATE_DRAFT_UPDATED => $this->t('…when <b>draft is updated</b>.'),
        WebformSubmissionInterface::STATE_CONVERTED => $this->t('…when anonymous <b>submission is converted</b> to authenticated.'),
        WebformSubmissionInterface::STATE_COMPLETED => $this->t('…when <b>submission is completed</b>.'),
        WebformSubmissionInterface::STATE_UPDATED => $this->t('…when <b>submission is updated</b>.'),
      ],
      '#required' => TRUE,
      '#access' => $results_disabled ? FALSE : TRUE,
      '#default_value' => $results_disabled ? [WebformSubmissionInterface::STATE_COMPLETED] : $this->configuration['states'],
    ];

    $form['actions'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Actions'),
    ];
    $form['actions']['sticky'] = [
      '#type' => 'select',
      '#title' => $this->t('Change status'),
      '#empty_option' => $this->t('- None -'),
      '#options' => [
        '1' => $this->t('Flag/Star'),
        '0' => $this->t('Unflag/Unstar'),
      ],
      '#default_value' => ($this->configuration['sticky'] === NULL) ? '' : ($this->configuration['sticky'] ? '1' : '0'),
    ];
    $form['actions']['locked'] = [
      '#type' => 'select',
      '#title' => $this->t('Change lock'),
      '#description' => $this->t('Webform submissions can only be unlocked programatically.'),
      '#empty_option' => $this->t('- None -'),
      '#options' => [
        '' => '',
        '1' => $this->t('Lock'),
        '0' => $this->t('Unlock'),
      ],
      '#default_value' => ($this->configuration['locked'] === NULL) ? '' : ($this->configuration['locked'] ? '1' : '0'),
    ];
    $form['actions']['notes'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'text',
      '#title' => $this->t('Append the below text to notes (Plain text)'),
      '#default_value' => $this->configuration['notes'],
    ];
    $form['actions']['message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Display message'),
      '#default_value' => $this->configuration['message'],
    ];
    $form['actions']['message_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Display message type'),
      '#options' => [
        'status' => $this->t('Status'),
        'error' => $this->t('Error'),
        'warning' => $this->t('Warning'),
        'info' => $this->t('Info'),
      ],
      '#default_value' => $this->configuration['message_type'],
    ];
    $form['actions']['data'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Update the below submission data. (YAML)'),
      '#default_value' => $this->configuration['data'],
    ];

    $elements_rows = [];
    $elements = $this->getWebform()->getElementsInitializedFlattenedAndHasValue();
    foreach ($elements as $element_key => $element) {
      $elements_rows[] = [
        $element_key,
        (isset($element['#title']) ? $element['#title'] : ''),
      ];
    }
    $form['actions']['elements'] = [
      '#type' => 'details',
      '#title' => $this->t('Available element keys'),
      'element_keys' => [
        '#type' => 'table',
        '#header' => [$this->t('Element key'), $this->t('Element title')],
        '#rows' => $elements_rows,
      ],
    ];
    $form['actions']['token_tree_link'] = $this->buildTokenTreeElement();

    // Development.
    $form['development'] = [
      '#type' => 'details',
      '#title' => $this->t('Development settings'),
    ];
    $form['development']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debugging'),
      '#description' => $this->t('If checked, trigger actions will be displayed onscreen to all users.'),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['debug'],
    ];

    $this->elementTokenValidate($form);

    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->hasAnyErrors()) {
      return;
    }

    // Validate data element keys.
    $elements = $this->getWebform()->getElementsInitializedFlattenedAndHasValue();
    $data = Yaml::decode($form_state->getValue('data')) ?: [];
    foreach ($data as $key => $value) {
      if (!isset($elements[$key])) {
        $form_state->setErrorByName('data', $this->t('%key is not valid element key.', ['%key' => $key]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->applyFormStateToConfiguration($form_state);

    // Cleanup states.
    $this->configuration['states'] = array_values(array_filter($this->configuration['states']));

    // Cleanup sticky.
    if ($form_state->getValue('sticky') === '') {
      $this->configuration['sticky'] = NULL;
    }

    // Cleanup locked.
    if ($form_state->getValue('locked') === '') {
      $this->configuration['locked'] = NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $state = $webform_submission->getWebform()->getSetting('results_disabled') ? WebformSubmissionInterface::STATE_COMPLETED : $webform_submission->getState();
    if (in_array($state, $this->configuration['states'])) {
      $this->executeAction($webform_submission);
    }
  }

  /****************************************************************************/
  // Action helper methods.
  /****************************************************************************/

  /**
   * Execute this action.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   */
  protected function executeAction(WebformSubmissionInterface $webform_submission) {
    // Set sticky.
    if ($this->configuration['sticky'] !== NULL) {
      $webform_submission->setSticky($this->configuration['sticky']);
    }

    // Set locked.
    if ($this->configuration['locked'] !== NULL) {
      $webform_submission->setLocked($this->configuration['locked']);
    }

    // Append notes.
    if ($this->configuration['notes']) {
      $notes = rtrim($webform_submission->getNotes());
      $notes .= ($notes ? PHP_EOL . PHP_EOL : '') . $this->replaceTokens($this->configuration['notes'], $webform_submission);
      $webform_submission->setNotes($notes);
    }

    // Set data.
    if ($this->configuration['data']) {
      $data = Yaml::decode($this->configuration['data']);
      $data = $this->replaceTokens($data, $webform_submission);
      foreach ($data as $key => $value) {
        $webform_submission->setElementData($key, $value);
      }
    }

    // Display message.
    if ($this->configuration['message']) {
      $message = WebformHtmlEditor::checkMarkup(
        $this->replaceTokens($this->configuration['message'], $webform_submission)
      );
      $message_type = $this->configuration['message_type'];
      $this->messenger()->addMessage(\Drupal::service('renderer')->renderPlain($message), $message_type);
    }

    // Resave the webform submission without trigger any hooks or handlers.
    $webform_submission->resave();

    // Display debugging information about the current action.
    $this->displayDebug($webform_submission);
  }

  /**
   * Display debugging information about the current action.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   */
  protected function displayDebug(WebformSubmissionInterface $webform_submission) {
    if (!$this->configuration['debug']) {
      return;
    }

    $build = [
      '#type' => 'details',
      '#title' => $this->t('Debug: Action: @title', ['@title' => $this->label()]),
      '#open' => TRUE,
    ];

    $state = $webform_submission->getWebform()->getSetting('results_disabled') ? WebformSubmissionInterface::STATE_COMPLETED : $webform_submission->getState();
    $build['state'] = [
      '#type' => 'item',
      '#title' => $this->t('State'),
      '#markup' => $state,
      '#wrapper_attributes' => ['class' => ['container-inline'], 'style' => 'margin: 0'],
    ];

    $build['sticky'] = [
      '#type' => 'item',
      '#title' => $this->t('Status'),
      '#markup' => ($this->configuration['sticky'] === NULL) ? '' : ($this->configuration['sticky'] ? $this->t('Flagged/Starred') : $this->t('Unflagged/Unstarred')),
      '#wrapper_attributes' => ['class' => ['container-inline'], 'style' => 'margin: 0'],
    ];

    $build['locked'] = [
      '#type' => 'item',
      '#title' => $this->t('Lock'),
      '#markup' => ($this->configuration['locked'] === NULL) ? '' : ($this->configuration['locked'] ? $this->t('Locked') : $this->t('Unlocked')),
      '#wrapper_attributes' => ['class' => ['container-inline'], 'style' => 'margin: 0'],
    ];

    $build['notes'] = [
      '#type' => 'item',
      '#title' => $this->t('Notes'),
      '#markup' => $this->configuration['notes'],
      '#wrapper_attributes' => ['class' => ['container-inline'], 'style' => 'margin: 0'],
    ];

    $build['data'] = [
      '#type' => 'item',
      '#title' => $this->t('Data'),
      '#markup' => $this->configuration['notes'] ? '<pre>' . htmlentities($this->configuration['notes']) . '</pre>' : '',
      '#wrapper_attributes' => ['class' => ['container-inline'], 'style' => 'margin: 0'],
    ];

    $build['message'] = [
      '#type' => 'item',
      '#title' => $this->t('Message'),
      '#markup' => $this->configuration['message'],
      '#wrapper_attributes' => ['class' => ['container-inline'], 'style' => 'margin: 0'],
    ];

    $build['message_type'] = [
      '#type' => 'item',
      '#title' => $this->t('Message type'),
      '#markup' => $this->configuration['message_type'],
      '#wrapper_attributes' => ['class' => ['container-inline'], 'style' => 'margin: 0'],
    ];

    $this->messenger()->addWarning(\Drupal::service('renderer')->renderPlain($build), TRUE);
  }

}
