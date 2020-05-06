<?php

namespace Drupal\webform\Plugin\WebformHandler;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform submission settings handler.
 *
 * @WebformHandler(
 *   id = "settings",
 *   label = @Translation("Settings"),
 *   category = @Translation("Settings"),
 *   description = @Translation("Allows Webform settings to be overridden based on submission data, source entity fields, and conditions."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 *   tokens = TRUE,
 * )
 */
class SettingsWebformHandler extends WebformHandlerBase {

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, WebformSubmissionConditionsValidatorInterface $conditions_validator, TypedConfigManagerInterface $typed_config, WebformTokenManagerInterface $token_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger_factory, $config_factory, $entity_type_manager, $conditions_validator);
    $this->typedConfigManager = $typed_config;
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
      $container->get('config.typed'),
      $container->get('webform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $configuration = $this->getConfiguration();
    $settings = $configuration['settings'];

    $setting_definitions = $this->getSettingsDefinitions();
    $setting_override = $this->getSettingsOverride();
    foreach ($setting_override as $name => $value) {
      switch ($setting_definitions[$name]['type']) {
        case 'label':
        case 'text':
        case 'string':
          $value = Unicode::truncate(strip_tags($value), 100, TRUE, TRUE);
          break;

        default:
          break;
      }
      $settings['settings'][$name] = [
        'title' => $setting_definitions[$name]['label'],
        'value' => ['#markup' => $value],
      ];
    }

    return [
      '#settings' => $settings,
    ] + parent::getSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'preview_title' => '',
      'preview_message' => '',
      'confirmation_url' => '',
      'confirmation_title' => '',
      'confirmation_message' => '',
      'debug' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Preview settings.
    $form['preview_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Preview settings'),
      '#open' => TRUE,
      '#access' => !empty($this->configuration['preview_title']) || !empty($this->configuration['preview_message']) || $this->getWebform()->hasPreview(),
    ];
    $form['preview_settings']['preview_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Preview page title'),
      '#description' => $this->t('The title displayed on the preview page.'),
      '#default_value' => $this->configuration['preview_title'],
    ];
    $form['preview_settings']['preview_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Preview message'),
      '#description' => $this->t('A message to be displayed on the preview page.'),
      '#default_value' => $this->configuration['preview_message'],
    ];
    $form['preview_settings']['token_tree_link'] = $this->buildTokenTreeElement();

    // Confirmation settings.
    $confirmation_type = $this->getWebform()->getSetting('confirmation_type');
    $has_confirmation_url = in_array($confirmation_type, [WebformInterface::CONFIRMATION_URL, WebformInterface::CONFIRMATION_URL_MESSAGE]);
    $has_confirmation_title = in_array($confirmation_type, [WebformInterface::CONFIRMATION_PAGE, WebformInterface::CONFIRMATION_MODAL]);
    $has_confirmation_message = !in_array($confirmation_type, [WebformInterface::CONFIRMATION_URL]);
    $form['confirmation_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Confirmation settings'),
      '#open' => TRUE,
    ];
    $form['confirmation_settings']['confirmation_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Confirmation URL'),
      '#description' => $this->t('URL to redirect the user to upon successful submission.'),
      '#default_value' => $this->configuration['confirmation_url'],
      '#access' => !empty($this->configuration['confirmation_url']) || $has_confirmation_url,
      '#maxlength' => NULL,
    ];
    $form['confirmation_settings']['confirmation_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Confirmation title'),
      '#description' => $this->t('Page title to be shown upon successful submission.'),
      '#default_value' => $this->configuration['confirmation_title'],
      '#access' => !empty($this->configuration['confirmation_title']) || $has_confirmation_title,
    ];
    $form['confirmation_settings']['confirmation_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Confirmation message'),
      '#description' => $this->t('Message to be shown upon successful submission.'),
      '#default_value' => $this->configuration['confirmation_message'],
      '#access' => !empty($this->configuration['confirmation_message']) || $has_confirmation_message,
    ];
    $form['confirmation_settings']['token_tree_link'] = $this->buildTokenTreeElement();

    // Custom settings.
    $custom_settings = $this->configuration;
    unset($custom_settings['debug']);
    $custom_settings = array_diff_key($custom_settings, $this->defaultConfiguration());
    $form['custom_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom settings'),
      '#open' => TRUE,
    ];
    $form['custom_settings']['custom'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Custom settings (YAML)'),
      '#description' => $this->t('Enter the setting name and value as YAML.'),
      '#default_value' => $custom_settings,
      // Must set #parents because custom is not a configuration value.
      // @see \Drupal\webform\Plugin\WebformHandler\SettingsWebformHandler::submitConfigurationForm
      '#parents' => ['settings', 'custom'],
    ];

    // Custom settings definitions.
    $form['custom_settings']['definitions'] = [
      '#type' => 'details',
      '#title' => $this->t('Available custom settings'),
    ];
    $rows = [];
    $webform_config_settings = $this->getSettingsDefinitions();
    foreach ($webform_config_settings as $name => $webform_config_setting) {
      $rows[] = [
        'name' => ['data' => ['#markup' => '<b>' . $name . '</b>']],
        'label' => $webform_config_setting['label'],
        'type' => $webform_config_setting['type'],
      ];
    }
    $form['custom_settings']['definitions']['warning'] = [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t('All of the below webform settings can be overridden but overriding certain settings can trigger unexpected results.'),
    ];
    $form['custom_settings']['definitions']['table'] = [
      '#type' => 'table',
      '#header' => [
        'name' => $this->t('Name'),
        'label' => $this->t('Label'),
        'type' => $this->t('Type'),
      ],
      '#rows' => $rows,
    ];

    // Development.
    $form['development'] = [
      '#type' => 'details',
      '#title' => $this->t('Development settings'),
    ];
    $form['development']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debugging'),
      '#description' => $this->t('If checked, settings will be displayed onscreen to all users.'),
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

    // Make sure custom settings are valid.
    $custom = $form_state->getValue('custom');
    if ($unknown_custom_settings = array_diff_key($custom, Webform::getDefaultSettings())) {
      $t_args = ['%name' => WebformArrayHelper::toString(array_keys($unknown_custom_settings))];
      $form_state->setErrorByName('custom', $this->t('Unknown custom %name setting(s).', $t_args));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Completely reset configuration so that custom configuration will always
    // be reset.
    $this->configuration = $this->defaultConfiguration();

    parent::submitConfigurationForm($form, $form_state);
    $this->applyFormStateToConfiguration($form_state);

    // Remove all empty strings from preview and confirmation settings.
    $this->configuration = array_filter($this->configuration);

    // Cast debug.
    $this->configuration['debug'] = (bool) $form_state->getValue('debug');

    // Append custom settings to configuration.
    $this->configuration += $form_state->getValue('custom');
  }

  /**
   * {@inheritdoc}
   */
  public function overrideSettings(array &$settings, WebformSubmissionInterface $webform_submission) {
    $settings_override = $this->getSubmissionSettingsOverride($webform_submission);
    foreach ($settings_override as $name => $value) {
      $settings[$name] = $value;
    }

    $this->displayDebug($webform_submission);
  }

  /****************************************************************************/
  // Debug handlers.
  /****************************************************************************/

  /**
   * Display debugging information about the current action.
   */
  protected function displayDebug(WebformSubmissionInterface $webform_submission) {
    if (!$this->configuration['debug']) {
      return;
    }

    $settings_definitions = $this->getSettingsDefinitions();
    $settings_override = $this->getSettingsOverride();
    $submission_settings_override = $this->getSubmissionSettingsOverride($webform_submission);

    // Set header.
    $header = [
      'name' => $this->t('Name'),
      'label' => [
        'data' => $this->t('Label'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'type' => [
        'data' => $this->t('Type'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'setting' => [
        'data' => $this->t('Setting Value'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'submission' => $this->t('Submission Value'),
    ];

    // Set rows.
    $rows = [];
    foreach ($settings_override as $name => $value) {
      $rows[] = [
        'name' => ['data' => ['#markup' => '<b>' . $name . '</b>']],
        'label' => $settings_definitions[$name]['label'],
        'type' => $settings_definitions[$name]['type'],
        'setting' => $settings_override[$name],
        'submission' => $submission_settings_override[$name],
      ];
    }

    $build = [
      '#type' => 'details',
      '#title' => $this->t('Debug: Settings: @title', ['@title' => $this->label()]),
      '#open' => TRUE,
    ];
    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
    $this->messenger()->addWarning(\Drupal::service('renderer')->renderPlain($build));
  }

  /****************************************************************************/
  // Settings helpers.
  /****************************************************************************/

  /**
   * Get webform setting definitions.
   *
   * @return array
   *   Webform setting definitions defined in webform.entity.webform.schema.yml
   */
  protected function getSettingsDefinitions() {
    $definition = $this->typedConfigManager->getDefinition('webform.webform.*');
    return $definition['mapping']['settings']['mapping'];
  }

  /**
   * Get overridden settings.
   *
   * @return array
   *   An associative array containing overridden settings.
   */
  protected function getSettingsOverride() {
    $settings = $this->configuration;
    unset($settings['debug']);
    $default_configuration = $this->defaultConfiguration();
    foreach ($settings as $name => $value) {
      if (isset($default_configuration[$name]) && $default_configuration[$name] === $value) {
        unset($settings[$name]);
      }
    }
    return $settings;
  }

  /**
   * Get webform submission's overridden settings.
   *
   * Replaces submissions token values and cast booleans and integers.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return array
   *   An associative array containing overridden settings.
   */
  protected function getSubmissionSettingsOverride(WebformSubmissionInterface $webform_submission) {
    $settings_definitions = $this->getSettingsDefinitions();
    $settings_override = $this->getSettingsOverride();
    foreach ($settings_override as $name => $value) {
      if (!isset($settings_definitions[$name])) {
        continue;
      }

      // Replace token value and cast booleans and integers.
      $type = $settings_definitions[$name]['type'];
      if (in_array($type, ['boolean', 'integer'])) {
        $value = $this->replaceTokens($value, $webform_submission);
        settype($value, $type);
        $settings_override[$name] = $value;
      }
    }
    return $settings_override;
  }

}
