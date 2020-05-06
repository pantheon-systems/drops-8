<?php

namespace Drupal\webform\Plugin\WebformVariant;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\Plugin\WebformVariantBase;
use Drupal\webform\Utility\WebformYaml;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform override variant.
 *
 * @WebformVariant(
 *   id = "override",
 *   label = @Translation("Override"),
 *   category = @Translation("Override"),
 *   description = @Translation("Override a webform's settings, elements, and handlers."),
 * )
 */
class OverrideWebformVariant extends WebformVariantBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a OverrideWebformVariant object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $config_factory);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'settings' => [],
      'elements' => '',
      'handlers' => [],
      'debug' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $webform = $this->getWebform();
    $form['overrides'] = [
      '#type' => 'details',
      '#title' => $this->t('Overrides'),
      '#open' => TRUE,
      '#access' => $this->currentUser->hasPermission('edit webform source'),
    ];

    // Settings.
    $form['overrides']['settings'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Settings (YAML)'),
      '#description' => $this->t('Enter the setting name and value as YAML.'),
      '#more_title' => $this->t('Default settings'),
      '#more' => [
        '#theme' => 'webform_codemirror',
        '#type' => 'yaml',
        '#code' => WebformYaml::encode($webform->getSettings()),
      ],
      '#parents' => ['settings', 'settings'],
      '#default_value' => $this->configuration['settings'],
    ];

    // Elements.
    $form['overrides']['elements'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Elements (YAML)'),
      '#description' => $this->t('Enter the element name and properties as YAML.'),
      '#more_title' => $this->t('Default elements'),
      '#more' => [
        '#theme' => 'webform_codemirror',
        '#type' => 'yaml',
        '#code' => WebformYaml::encode($webform->getElementsDecodedAndFlattened()),
      ],
      '#parents' => ['settings', 'elements'],
      '#default_value' => $this->configuration['elements'],
    ];

    // Handlers.
    $handlers = $webform->get('handlers');
    foreach ($handlers as &$handler) {
      unset($handler['id'], $handler['handler_id']);
    }
    $form['overrides']['handlers'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Handlers (YAML)'),
      '#description' => $this->t('Enter the handler id and settings as YAML.'),
      '#more_title' => $this->t('Default handlers'),
      '#more' => [
        '#theme' => 'webform_codemirror',
        '#type' => 'yaml',
        '#code' => WebformYaml::encode($handlers),
      ],
      '#parents' => ['settings', 'handlers'],
      '#default_value' => $this->configuration['handlers'],
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
      '#parents' => ['settings', 'debug'],
      '#default_value' => $this->configuration['debug'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $webform = $this->getWebform();
    $values = $form_state->getValues();

    // Validate settings names.
    $settings = $webform->getSettings();
    foreach ($values['settings'] as $setting_name => $setting_value) {
      if (!isset($settings[$setting_name])) {
        $form_state->setErrorByName('settings', $this->t('Setting %name is not a valid setting name.', ['%name' => $setting_name]));
      }
    }

    // Validate element keys.
    $elements = Yaml::decode($values['elements']) ?: [];
    if ($elements) {
      foreach ($elements as $element_key => $element_properties) {
        $element = $webform->getElement($element_key);
        if (!$element) {
          $form_state->setErrorByName('elements', $this->t('Element %key is not a valid element key.', ['%key' => $element_key]));
        }
      }
    }

    // Validate handler ids.
    foreach ($values['handlers'] as $handler_id => $handler_configuration) {
      if (!$webform->getHandlers()->has($handler_id)) {
        $form_state->setErrorByName('handlers', $this->t('Handler %id is not a valid handler id.', ['%id' => $handler_id]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration = $form_state->getValues();
    $this->configuration['debug'] = (boolean) $this->configuration['debug'];
  }

  /**
   * {@inheritdoc}
   */
  public function applyVariant() {
    $webform = $this->getWebform();

    // Override settings.
    if ($this->configuration['settings']) {
      $settings = $webform->getSettings();
      foreach ($this->configuration['settings'] as $setting_name => $setting_value) {
        if (isset($settings[$setting_name])) {
          $settings[$setting_name] = $setting_value;
        }
      }
      $webform->setSettings($settings);
    }

    // Override elements.
    $elements = Yaml::decode($this->configuration['elements']) ?: [];
    if ($elements) {
      foreach ($elements as $element_key => $element_properties) {
        $element = $webform->getElement($element_key);
        if (!$element) {
          continue;
        }
        $webform->setElementProperties($element_key, $element_properties + $element);
      }
    }

    // Override handlers.
    if ($this->configuration['handlers']) {
      foreach ($this->configuration['handlers'] as $handler_id => $handler_configuration) {
        if (!$webform->getHandlers()->has($handler_id)) {
          continue;
        }
        $handler = $webform->getHandler($handler_id);

        $configuration = $handler->getConfiguration();
        foreach ($handler_configuration as $configuration_key => $configuration_value) {
          if (!isset($configuration[$configuration_key])) {
            continue;
          }

          if ($configuration_key === 'settings') {
            $configuration[$configuration_key] = $configuration_value + $configuration[$configuration_key];
          }
          else {
            $configuration[$configuration_key] = $configuration_value;
          }
        }
        $handler->setConfiguration($configuration);
      }
    }

    // Debug.
    $this->debug();

    return TRUE;
  }

  /****************************************************************************/
  // Debug and exception handlers.
  /****************************************************************************/

  /**
   * Display debugging information.
   */
  protected function debug() {
    if (empty($this->configuration['debug'])) {
      return;
    }

    $build = [
      '#type' => 'details',
      '#title' => $this->t('Debug: Override: @title', ['@title' => $this->label()]),
    ];

    // Notes.
    if ($notes = $this->getNotes()) {
      $build['notes'] = [
        '#type' => 'item',
        '#title' => $this->t('Notes'),
        'notes' => WebformHtmlEditor::checkMarkup($notes),
      ];
    }

    // Settings.
    if ($this->configuration['settings']) {
      $build['settings'] = [
        '#type' => 'item',
        '#title' => $this->t('Settings'),
        'yaml' => [
          '#theme' => 'webform_codemirror',
          '#type' => 'yaml',
          '#code' => WebformYaml::encode($this->configuration['settings']),
        ],
      ];
    }

    // Elements.
    if ($this->configuration['elements']) {
      $build['elements'] = [
        '#type' => 'item',
        '#title' => $this->t('Elements'),
        'yaml' => [
          '#theme' => 'webform_codemirror',
          '#type' => 'yaml',
          '#code' => $this->configuration['elements'],
        ],
      ];
    }

    // Handlers.
    if ($this->configuration['handlers']) {
      $build['handlers'] = [
        '#type' => 'item',
        '#title' => $this->t('Handlers'),
        'yaml' => [
          '#theme' => 'webform_codemirror',
          '#type' => 'yaml',
          '#code' => WebformYaml::encode($this->configuration['handlers']),
        ],
      ];
    }

    $this->messenger()->addWarning(\Drupal::service('renderer')->renderPlain($build));
  }

}
