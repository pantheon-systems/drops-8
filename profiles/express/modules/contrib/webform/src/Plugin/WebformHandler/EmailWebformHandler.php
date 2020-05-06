<?php

namespace Drupal\webform\Plugin\WebformHandler;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\webform\Element\WebformAjaxElementTrait;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Element\WebformSelectOther;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\Plugin\WebformHandlerMessageInterface;
use Drupal\webform\Twig\WebformTwigExtension;
use Drupal\webform\Utility\Mail;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformOptionsHelper;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformThemeManagerInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Emails a webform submission.
 *
 * @WebformHandler(
 *   id = "email",
 *   label = @Translation("Email"),
 *   category = @Translation("Notification"),
 *   description = @Translation("Sends a webform submission via an email."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 *   tokens = TRUE,
 * )
 */
class EmailWebformHandler extends WebformHandlerBase implements WebformHandlerMessageInterface {

  use WebformAjaxElementTrait;

  /**
   * Other option value.
   */
  const OTHER_OPTION = '_other_';

  /**
   * Default option value.
   */
  const EMPTY_OPTION = '_empty_';

  /**
   * Default option value.
   *
   * @see \Drupal\webform\Plugin\WebformHandler\EmailWebformHandler::getMessageEmails
   */
  const DEFAULT_OPTION = '_default_';

  /**
   * Default value. (This is used by the handler's settings.)
   */
  const DEFAULT_VALUE = '_default';

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * A mail manager for sending email.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * The webform theme manager.
   *
   * @var \Drupal\webform\WebformThemeManagerInterface
   */
  protected $themeManager;

  /**
   * A webform element plugin manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Cache of default configuration values.
   *
   * @var array
   */
  protected $defaultValues;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, WebformSubmissionConditionsValidatorInterface $conditions_validator, AccountInterface $current_user, ModuleHandlerInterface $module_handler, LanguageManagerInterface $language_manager, MailManagerInterface $mail_manager, WebformThemeManagerInterface $theme_manager, WebformTokenManagerInterface $token_manager, WebformElementManagerInterface $element_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger_factory, $config_factory, $entity_type_manager, $conditions_validator);
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
    $this->mailManager = $mail_manager;
    $this->themeManager = $theme_manager;
    $this->tokenManager = $token_manager;
    $this->elementManager = $element_manager;
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
      $container->get('current_user'),
      $container->get('module_handler'),
      $container->get('language_manager'),
      $container->get('plugin.manager.mail'),
      $container->get('webform.theme_manager'),
      $container->get('webform.token_manager'),
      $container->get('plugin.manager.webform.element')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $settings = $this->getEmailConfiguration();

    // Simplify the [webform_submission:values:.*] tokens.
    array_walk($settings, function (&$value, $key) {
      if (is_string($value)) {
        $value = preg_replace('/\[webform:([^:]+)\]/', '[\1]', $value);
        $value = preg_replace('/\[webform_role:([^:]+)\]/', '[\1]', $value);
        $value = preg_replace('/\[webform_access:type:([^:]+)\]/', '[\1]', $value);
        $value = preg_replace('/\[webform_group:role:([^:]+)\]/', '[group:\1]', $value);
        $value = preg_replace('/\[webform_group:owner:mail\]/', '[group:owner]', $value);
        $value = preg_replace('/\[webform_submission:(?:node|source_entity|values):([^]]+)\]/', '[\1]', $value);
        $value = preg_replace('/\[webform_submission:([^]]+)\]/', '[\1]', $value);
        $value = preg_replace('/(:raw|:value)(:html)?\]/', ']', $value);
      }
    });
    // Set state.
    $states = [
      WebformSubmissionInterface::STATE_DRAFT_CREATED => $this->t('Draft created'),
      WebformSubmissionInterface::STATE_DRAFT_UPDATED => $this->t('Draft updated'),
      WebformSubmissionInterface::STATE_CONVERTED => $this->t('Converted'),
      WebformSubmissionInterface::STATE_COMPLETED => $this->t('Completed'),
      WebformSubmissionInterface::STATE_UPDATED => $this->t('Updated'),
      WebformSubmissionInterface::STATE_DELETED => $this->t('Deleted'),
    ];
    $settings['states'] = array_intersect_key($states, array_combine($settings['states'], $settings['states']));

    // Set theme name.
    if ($settings['theme_name']) {
      $settings['theme_name'] = $this->themeManager->getThemeName($settings['theme_name']);
    }

    return [
      '#settings' => $settings,
    ] + parent::getSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    parent::setConfiguration($configuration);

    // Make sure 'default' is converted to '_default'.
    // @see https://www.drupal.org/project/webform/issues/2980470
    // @see webform_update_8131()
    // @todo Webform 8.x-6.x: Remove the below code.
    $default_configuration = $this->defaultConfiguration();
    foreach ($this->configuration as $key => $value) {
      if ($value === 'default'
        && isset($default_configuration[$key])
        && $default_configuration[$key] === static::DEFAULT_VALUE) {
        $this->configuration[$key] = static::DEFAULT_VALUE;
      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'states' => [WebformSubmissionInterface::STATE_COMPLETED],
      'to_mail' => static::DEFAULT_VALUE,
      'to_options' => [],
      'cc_mail' => '',
      'cc_options' => [],
      'bcc_mail' => '',
      'bcc_options' => [],
      'from_mail' => static::DEFAULT_VALUE,
      'from_options' => [],
      'from_name' => static::DEFAULT_VALUE,
      'subject' => static::DEFAULT_VALUE,
      'body' => static::DEFAULT_VALUE,
      'excluded_elements' => [],
      'ignore_access' => FALSE,
      'exclude_empty' => TRUE,
      'exclude_empty_checkbox' => FALSE,
      'exclude_attachments' => FALSE,
      'html' => TRUE,
      'attachments' => FALSE,
      'twig' => FALSE,
      'debug' => FALSE,
      'reply_to' => '',
      'return_path' => '',
      'sender_mail' => '',
      'sender_name' => '',
      'theme_name' => '',
      'parameters' => [],
    ];
  }

  /**
   * Get configuration default values.
   *
   * @return array
   *   Configuration default values.
   */
  protected function getDefaultConfigurationValues() {
    if (isset($this->defaultValues)) {
      return $this->defaultValues;
    }

    $webform_settings = $this->configFactory->get('webform.settings');
    $site_settings = $this->configFactory->get('system.site');
    $body_format = ($this->configuration['html']) ? 'html' : 'text';
    $default_to_mail = $webform_settings->get('mail.default_to_mail') ?: $site_settings->get('mail') ?: ini_get('sendmail_from');
    $default_from_mail = $webform_settings->get('mail.default_from_mail') ?: $site_settings->get('mail') ?: ini_get('sendmail_from');

    $this->defaultValues = [
      'states' => [WebformSubmissionInterface::STATE_COMPLETED],
      'to_mail' => $default_to_mail,
      'to_options' => [],
      'cc_mail' => $default_to_mail,
      'cc_options' => [],
      'bcc_mail' => $default_to_mail,
      'bcc_options' => [],
      'from_mail' => $default_from_mail,
      'from_options' => [],
      'from_name' => $webform_settings->get('mail.default_from_name') ?: $site_settings->get('name'),
      'subject' => $webform_settings->get('mail.default_subject') ?: 'Webform submission from: [webform_submission:source-entity]',
      'body' => $this->getBodyDefaultValues($body_format),
      'reply_to' => $webform_settings->get('mail.default_reply_to') ?: '',
      'return_path' => $webform_settings->get('mail.default_return_path') ?: '',
      'sender_mail' => $webform_settings->get('mail.default_sender_mail') ?: '',
      'sender_name' => $webform_settings->get('mail.default_sender_name') ?: '',
      'theme_name' => '',
    ];

    return $this->defaultValues;
  }

  /**
   * Get configuration default value.
   *
   * @param string $name
   *   Configuration name.
   *
   * @return string|array
   *   Configuration default value.
   */
  protected function getDefaultConfigurationValue($name) {
    $default_values = $this->getDefaultConfigurationValues();
    return $default_values[$name];
  }

  /**
   * Get mail configuration values.
   *
   * @return array
   *   An associative array containing email configuration values,
   *   along with the default configuration values.
   */
  public function getEmailConfiguration() {
    $configuration = $this->getConfiguration();
    $email = [];
    foreach ($configuration['settings'] as $key => $value) {
      $email[$key] = ($value === static::DEFAULT_VALUE) ? $this->getDefaultConfigurationValue($key) : $value;
    }
    return $email;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $this->applyFormStateToConfiguration($form_state);

    // Get options, mail, and text elements as options (text/value).
    $text_element_options_value = [];
    $text_element_options_raw = [];
    $name_element_options = [];
    $mail_element_options = [];
    $options_element_options = [];

    $elements = $this->webform->getElementsInitializedAndFlattened();
    foreach ($elements as $element_key => $element) {
      $element_plugin = $this->elementManager->getElementInstance($element);
      if (!$element_plugin->isInput($element) || !isset($element['#type'])) {
        continue;
      }

      // Set title.
      $element_title = (isset($element['#title'])) ? new FormattableMarkup('@title (@key)', ['@title' => $element['#title'], '@key' => $element_key]) : $element_key;

      // Add options element token, which can include multiple values.
      if (isset($element['#options'])) {
        $options_element_options["[webform_submission:values:$element_key:raw]"] = $element_title;
      }

      // Multiple value elements can NOT be used as a tokens.
      if ($element_plugin->hasMultipleValues($element)) {
        continue;
      }

      if (!$element_plugin->isComposite()) {
        // Add text element value and raw tokens.
        $text_element_options_value["[webform_submission:values:$element_key:value]"] = $element_title;
        $text_element_options_raw["[webform_submission:values:$element_key:raw]"] = $element_title;

        // Add name element token.
        $name_element_options["[webform_submission:values:$element_key:raw]"] = $element_title;

        // Add mail element token.
        if (in_array($element['#type'], ['email', 'hidden', 'value', 'textfield', 'webform_email_multiple', 'webform_email_confirm'])) {
          $mail_element_options["[webform_submission:values:$element_key:raw]"] = $element_title;
        }
      }

      // Element type specific tokens.
      switch ($element['#type']) {
        case 'webform_name':
          // Allow 'webform_name' composite to be used a value token.
          $name_element_options["[webform_submission:values:$element_key:value]"] = $element_title;
          break;

        case 'text_format':
          // Allow 'text_format' composite to be used a value token.
          $text_element_options_value["[webform_submission:values:$element_key]"] = $element_title;
          break;
      }

      // Handle composite sub elements.
      if ($element_plugin instanceof WebformCompositeBase) {
        $composite_elements = $element_plugin->getCompositeElements();
        foreach ($composite_elements as $composite_key => $composite_element) {
          $composite_element_plugin = $this->elementManager->getElementInstance($element);
          if (!$composite_element_plugin->isInput($element) || !isset($composite_element['#type'])) {
            continue;
          }

          // Set composite title.
          if (isset($element['#title'])) {
            $f_args = [
              '@title' => $element['#title'],
              '@composite_title' => $composite_element['#title'],
              '@key' => $element_key,
              '@composite_key' => $composite_key,
            ];
            $composite_title = new FormattableMarkup('@title: @composite_title (@key: @composite_key)', $f_args);
          }
          else {
            $composite_title = "$element_key:$composite_key";
          }

          // Add name element token. Only applies to basic (not composite) elements.
          $name_element_options["[webform_submission:values:$element_key:$composite_key:raw]"] = $composite_title;

          // Add mail element token.
          if (in_array($composite_element['#type'], ['email', 'webform_email_multiple', 'webform_email_confirm'])) {
            $mail_element_options["[webform_submission:values:$element_key:$composite_key:raw]"] = $composite_title;
          }
        }
      }
    }

    // Get roles.
    $roles_element_options = [];
    if ($roles = $this->configFactory->get('webform.settings')->get('mail.roles')) {
      $role_names = array_map('\Drupal\Component\Utility\Html::escape', user_role_names(TRUE));
      if (!in_array('authenticated', $roles)) {
        $role_names = array_intersect_key($role_names, array_combine($roles, $roles));
      }
      foreach ($role_names as $role_name => $role_label) {
        $roles_element_options["[webform_role:$role_name]"] = new FormattableMarkup('@title (@key)', ['@title' => $role_label, '@key' => $role_name]);
      }
    }

    // Get email and name other.
    $other_element_email_options = [
      '[site:mail]' => 'Site email address',
      '[current-user:mail]' => 'Current user email address [Authenticated only]',
      '[webform:author:mail]' => 'Webform author email address',
      '[webform_submission:user:mail]' => 'Webform submission owner email address [Authenticated only]',
    ];
    $other_element_name_options = [
      '[site:name]' => 'Site name',
      '[current-user:display-name]' => 'Current user display name',
      '[current-user:account-name]' => 'Current user account name',
      '[webform:author:display-name]' => 'Webform author display name',
      '[webform:author:account-name]' => 'Webform author account name',
      '[webform_submission:author:display-name]' => 'Webform submission author display name',
      '[webform_submission:author:account-name]' => 'Webform submission author account name',
    ];

    // Disable client-side HTML5 validation which is having issues with hidden
    // element validation.
    // @see http://stackoverflow.com/questions/22148080/an-invalid-form-control-with-name-is-not-focusable
    $form['#attributes']['novalidate'] = 'novalidate';

    // To.
    $form['to'] = [
      '#type' => 'details',
      '#title' => $this->t('Send to'),
      '#open' => TRUE,
    ];
    $form['to']['to_mail'] = $this->buildElement('to_mail', $this->t('To email'), $this->t('To email address'), TRUE, $mail_element_options, $options_element_options, $roles_element_options, $other_element_email_options);
    $form['to']['cc_mail'] = $this->buildElement('cc_mail', $this->t('CC email'), $this->t('CC email address'), FALSE, $mail_element_options, $options_element_options, $roles_element_options, $other_element_email_options);
    $form['to']['bcc_mail'] = $this->buildElement('bcc_mail', $this->t('BCC email'), $this->t('BCC email address'), FALSE, $mail_element_options, $options_element_options, $roles_element_options, $other_element_email_options);
    $token_types = ['webform', 'webform_submission'];
    // Show webform role tokens if they have been specified.
    if (!empty($roles_element_options)) {
      $token_types[] = 'webform_role';
    }
    if ($this->moduleHandler->moduleExists('webform_access')) {
      $token_types[] = 'webform_access';
    }
    if ($this->moduleHandler->moduleExists('webform_group')) {
      $token_types[] = 'webform_group';
    }
    $form['to']['token_tree_link'] = $this->buildTokenTreeElement($token_types);

    if (empty($roles_element_options) && $this->currentUser->hasPermission('administer webform')) {
      $route_name = 'webform.config.handlers';
      $route_destination = Url::fromRoute('entity.webform.handlers', ['webform' => $this->getWebform()->id()])->toString();
      $route_options = ['query' => ['destination' => $route_destination]];
      $t_args = [':href' => Url::fromRoute($route_name, [], $route_options)->toString()];
      $form['to']['roles_message'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => $this->t('Please note: You can select which <strong>user roles</strong> are available to receive webform emails by going to the Webform module\'s <a href=":href">admin settings</a> form.', $t_args),
        '#message_close' => TRUE,
        '#message_id' => 'webform_email_roles_message',
        '#message_storage' => WebformMessage::STORAGE_USER,
      ];
    }

    // From.
    $form['from'] = [
      '#type' => 'details',
      '#title' => $this->t('Send from'),
      '#open' => TRUE,
    ];
    $form['from']['from_mail'] = $this->buildElement('from_mail', $this->t('From email'), $this->t('From email address'), TRUE, $mail_element_options, $options_element_options, NULL, $other_element_email_options);
    $form['from']['from_name'] = $this->buildElement('from_name', $this->t('From name'), $this->t('From name'), FALSE, $name_element_options, NULL, NULL, $other_element_name_options);
    $form['from']['token_tree_link'] = $this->buildTokenTreeElement();

    // Message.
    $form['message'] = [
      '#type' => 'details',
      '#title' => $this->t('Message'),
      '#open' => TRUE,
    ];
    $form['message'] += $this->buildElement('subject', $this->t('Subject'), $this->t('subject'), FALSE, $text_element_options_raw);

    $has_edit_twig_access = (WebformTwigExtension::hasEditTwigAccess() || $this->configuration['twig']);

    // Message: Body.
    // Building a custom select other element that toggles between
    // HTML (CKEditor), Plain text (CodeMirror), and Twig (CodeMirror)
    // custom body elements.
    $body_options = [];
    $body_options[WebformSelectOther::OTHER_OPTION] = $this->t('Custom body…');
    if ($has_edit_twig_access) {
      $body_options['twig'] = $this->t('Twig template…');
    }
    $body_options[static::DEFAULT_VALUE] = $this->t('Default');
    $body_options[(string) $this->t('Elements')] = $text_element_options_value;

    // Get default format.
    $body_default_format = ($this->configuration['html']) ? 'html' : 'text';

    // Get default values.
    $body_default_values = $this->getBodyDefaultValues();

    // Get custom default values which are the same as default values.
    $body_custom_default_values = $this->getBodyDefaultValues();

    // Set up default Twig body and convert tokens to use the
    // webform_token() Twig function.
    // @see \Drupal\webform\Twig\WebformTwigExtension
    $twig_default_body = $body_custom_default_values[$body_default_format];
    $twig_default_body = preg_replace('/(\[[^]]+\])/', '{{ webform_token(\'\1\', webform_submission) }}', $twig_default_body);
    $body_custom_default_values['twig'] = $twig_default_body;

    // Look at the 'body' and determine the body select and custom
    // default values.
    if (WebformOptionsHelper::hasOption($this->configuration['body'], $body_options)) {
      $body_select_default_value = $this->configuration['body'];
    }
    elseif ($this->configuration['twig']) {
      $body_select_default_value = 'twig';
      $body_custom_default_values['twig'] = $this->configuration['body'];
    }
    else {
      $body_select_default_value = WebformSelectOther::OTHER_OPTION;
      $body_custom_default_values[$body_default_format] = $this->configuration['body'];
    }

    // Build body select menu.
    $form['message']['body'] = [
      '#type' => 'select',
      '#title' => $this->t('Body'),
      '#options' => $body_options,
      '#required' => TRUE,
      '#default_value' => $body_select_default_value,
    ];
    foreach ($body_default_values as $format => $default_value) {
      if ($format == 'html') {
        $form['message']['body_custom_' . $format] = [
          '#type' => 'webform_html_editor',
          '#format' => $this->configFactory->get('webform.settings')->get('html_editor.mail_format'),
        ];
      }
      else {
        $form['message']['body_custom_' . $format] = [
          '#type' => 'webform_codemirror',
          '#mode' => $format,
        ];
      }
      $form['message']['body_custom_' . $format] += [
        '#title' => $this->t('Body custom value (@format)', ['@format' => $format]),
        '#title_display' => 'hidden',
        '#default_value' => $body_custom_default_values[$format],
        '#states' => [
          'visible' => [
            ':input[name="settings[body]"]' => ['value' => WebformSelectOther::OTHER_OPTION],
            ':input[name="settings[html]"]' => ['checked' => ($format == 'html') ? TRUE : FALSE],
          ],
          'required' => [
            ':input[name="settings[body]"]' => ['value' => WebformSelectOther::OTHER_OPTION],
            ':input[name="settings[html]"]' => ['checked' => ($format == 'html') ? TRUE : FALSE],
          ],
        ],
      ];
      // Must set #parents because body_custom_* is not a configuration value.
      // @see \Drupal\webform\Plugin\WebformHandler\EmailWebformHandler::validateConfigurationForm
      $form['message']['body_custom_' . $format]['#parents'] = ['settings', 'body_custom_' . $format];

      // Default body.
      $form['message']['body_default_' . $format] = [
        '#type' => 'webform_codemirror',
        '#mode' => $format,
        '#title' => $this->t('Body default value (@format)', ['@format' => $format]),
        '#title_display' => 'hidden',
        '#default_value' => $body_default_values[$format],
        '#attributes' => ['readonly' => 'readonly', 'disabled' => 'disabled'],
        '#states' => [
          'visible' => [
            ':input[name="settings[body]"]' => ['value' => static::DEFAULT_VALUE],
            ':input[name="settings[html]"]' => ['checked' => ($format == 'html') ? TRUE : FALSE],
          ],
        ],
      ];
    }
    // Twig body with help.
    $form['message']['body_custom_twig'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'twig',
      '#title' => $this->t('Body custom value (Twig)'),
      '#title_display' => 'hidden',
      '#default_value' => $body_custom_default_values['twig'],
      '#access' => $has_edit_twig_access,
      '#states' => [
        'visible' => [
          ':input[name="settings[body]"]' => ['value' => 'twig'],
        ],
        'required' => [
          ':input[name="settings[body]"]' => ['value' => 'twig'],
        ],
      ],
      // Must set #parents because body_custom_twig is not a configuration value.
      // @see \Drupal\webform\Plugin\WebformHandler\EmailWebformHandler::validateConfigurationForm
      '#parents' => ['settings', 'body_custom_twig'],
    ];
    $form['message']['body_custom_twig_help'] = WebformTwigExtension::buildTwigHelp() + [
      '#access' => $has_edit_twig_access,
      '#states' => [
        'visible' => [
          ':input[name="settings[body]"]' => ['value' => 'twig'],
        ],
      ],
    ];
    // Tokens.
    $form['message']['token_tree_link'] = $this->buildTokenTreeElement();

    // Elements.
    $form['elements'] = [
      '#type' => 'details',
      '#title' => $this->t('Included email values/markup'),
      '#description' => $this->t('The selected elements will be included in the [webform_submission:values] token. Individual values may still be printed if explicitly specified as a [webform_submission:values:?] in the email body template.'),
      '#open' => $this->configuration['excluded_elements'] ? TRUE : FALSE,
    ];
    $form['elements']['excluded_elements'] = [
      '#type' => 'webform_excluded_elements',
      '#exclude_markup' => FALSE,
      '#webform_id' => $this->webform->id(),
      '#default_value' => $this->configuration['excluded_elements'],
    ];
    $form['elements']['ignore_access'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always include elements with private and restricted access'),
      '#description' => $this->t('If checked, access controls for included element will be ignored.'),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['ignore_access'],
    ];
    $form['elements']['exclude_empty'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude empty elements'),
      '#description' => $this->t('If checked, empty elements will be excluded from the email values.'),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['exclude_empty'],
    ];
    $form['elements']['exclude_empty_checkbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude unselected checkboxes'),
      '#description' => $this->t('If checked, empty checkboxes will be excluded from the email values.'),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['exclude_empty_checkbox'],
    ];
    $form['elements']['exclude_attachments'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude file elements with attachments'),
      '#return_value' => TRUE,
      '#description' => $this->t('If checked, file attachments will be excluded from the email values, but the selected element files will still be attached to the email.'),
      '#default_value' => $this->configuration['exclude_attachments'],
      '#access' => $this->getWebform()->hasAttachments(),
      '#disabled' => !$this->supportsAttachments(),
      '#states' => [
        'visible' => [':input[name="settings[attachments]"]' => ['checked' => TRUE]],
      ],
    ];
    $elements = $this->webform->getElementsInitializedFlattenedAndHasValue();
    foreach ($elements as $element) {
      if (!empty($element['#access_view_roles']) || !empty($element['#private'])) {
        $form['elements']['ignore_access_message'] = [
          '#type' => 'webform_message',
          '#message_message' => $this->t('This webform contains private and/or restricted access elements, which will only be included if the user submitting the form has access to these elements.'),
          '#message_type' => 'warning',
          '#states' => [
            'visible' => [':input[name="settings[ignore_access]"]' => ['checked' => FALSE]],
          ],
        ];
        break;
      }
    }

    // Attachments.
    $form['attachments'] = [
      '#type' => 'details',
      '#title' => $this->t('Attachments'),
      '#access' => $this->getWebform()->hasAttachments(),
    ];
    if (!$this->supportsAttachments()) {
      $t_args = [
        ':href_smtp' => 'https://www.drupal.org/project/smtp',
        ':href_mailsystem' => 'https://www.drupal.org/project/mailsystem',
        ':href_swiftmailer' => 'https://www.drupal.org/project/swiftmailer',
      ];
      $form['attachments']['attachments_message'] = [
        '#type' => 'webform_message',
        '#message_message' => $this->t('To send email attachments, please install and configure the <a href=":href_smtp">SMTP Authentication Support</a> module or the <a href=":href_mailsystem">Mail System</a> and <a href=":href_swiftmailer">SwiftMailer</a> module.', $t_args),
        '#message_type' => 'warning',
        '#message_close' => TRUE,
        '#message_storage' => WebformMessage::STORAGE_SESSION,
      ];
    }
    $form['attachments']['attachments'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include files as attachments'),
      '#description' => $this->t('If checked, only file upload elements selected in the above included email values will be attached to the email.'),
      '#return_value' => TRUE,
      '#disabled' => !$this->supportsAttachments(),
      '#default_value' => $this->configuration['attachments'],
    ];

    // Additional.
    $results_disabled = $this->getWebform()->getSetting('results_disabled');
    $form['additional'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Additional settings'),
    ];
    // Settings: States.
    $form['additional']['states'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Send email'),
      '#options' => [
        WebformSubmissionInterface::STATE_DRAFT_CREATED => $this->t('…when <b>draft is created</b>.'),
        WebformSubmissionInterface::STATE_DRAFT_UPDATED => $this->t('…when <b>draft is updated</b>.'),
        WebformSubmissionInterface::STATE_CONVERTED => $this->t('…when anonymous <b>submission is converted</b> to authenticated.'),
        WebformSubmissionInterface::STATE_COMPLETED => $this->t('…when <b>submission is completed</b>.'),
        WebformSubmissionInterface::STATE_UPDATED => $this->t('…when <b>submission is updated</b>.'),
        WebformSubmissionInterface::STATE_DELETED => $this->t('…when <b>submission is deleted</b>.'),
        WebformSubmissionInterface::STATE_LOCKED => $this->t('…when <b>submission is locked</b>.'),
      ],
      '#access' => $results_disabled ? FALSE : TRUE,
      '#default_value' => $results_disabled ? [WebformSubmissionInterface::STATE_COMPLETED] : $this->configuration['states'],
    ];
    $form['additional']['states_message'] = [
      '#type' => 'webform_message',
      '#message_message' => $this->t("Because no submission state is checked, this email can only be sent using the 'Resend' form and/or custom code."),
      '#message_type' => 'warning',
      '#states' => [
        'visible' => [
          ':input[name^="settings[states]"]' => ['checked' => FALSE],
        ],
      ],
    ];
    // Settings: Reply-to.
    $form['additional']['reply_to'] = $this->buildElement('reply_to', $this->t('Reply-to email'), $this->t('Reply-to email address'), FALSE, $mail_element_options, NULL, NULL, $other_element_email_options);
    // Settings: Return path.
    $form['additional']['return_path'] = $this->buildElement('return_path', $this->t('Return path'), $this->t('Return path email address'), FALSE, $mail_element_options, NULL, NULL, $other_element_email_options);
    // Settings: Sender mail.
    $form['additional']['sender_mail'] = $this->buildElement('sender_mail', $this->t('Sender email'), $this->t('Sender email address'), FALSE, $mail_element_options, $options_element_options, NULL, $other_element_email_options);
    // Settings: Sender name.
    $form['additional']['sender_name'] = $this->buildElement('sender_name', $this->t('Sender name'), $this->t('Sender name'), FALSE, $name_element_options, NULL, NULL, $other_element_name_options);

    // Settings: HTML.
    $form['additional']['html'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send email as HTML'),
      '#return_value' => TRUE,
      '#access' => $this->supportsHtml(),
      '#default_value' => $this->configuration['html'],
    ];

    // Setting: Themes.
    $form['additional']['theme_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Theme to render this email'),
      '#description' => $this->t('Select the theme that will be used to render this email.'),
      '#options' => $this->themeManager->getThemeNames(),
      '#default_value' => $this->configuration['theme_name'],
    ];

    $form['additional']['parameters'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Custom parameters'),
      '#description' => $this->t('Enter additional custom parameters to be appended to the email message\'s parameters. Custom parameters are used by <a href=":href">email related add-on modules</a>.', [':href' => 'https://www.drupal.org/docs/8/modules/webform/webform-add-ons#mail']),
      '#default_value' => $this->configuration['parameters'],
    ];

    // Development.
    $form['development'] = [
      '#type' => 'details',
      '#title' => $this->t('Development settings'),
    ];
    $form['development']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debugging'),
      '#description' => $this->t('If checked, sent emails will be displayed onscreen to all users.'),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['debug'],
    ];

    // ISSUE: TranslatableMarkup is breaking the #ajax.
    // WORKAROUND: Convert all Render/Markup to strings.
    WebformElementHelper::convertRenderMarkupToStrings($form);

    $this->elementTokenValidate($form, $token_types);

    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    $values = $form_state->getValues();

    // Set custom body based on the selected format.
    $values['twig'] = FALSE;
    switch ($values['body']) {
      case 'twig':
        $values['body'] = $values['body_custom_twig'];
        $values['twig'] = TRUE;
        break;

      case WebformSelectOther::OTHER_OPTION:
        $body_format = ($values['html']) ? 'html' : 'text';
        $values['body'] = $values['body_custom_' . $body_format];
        break;
    }

    $form_state->setValues($values);
    $this->applyFormStateToConfiguration($form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValues();

    // Cleanup states.
    $values['states'] = array_values(array_filter($values['states']));

    foreach ($this->configuration as $name => $value) {
      if (isset($values[$name])) {
        // Convert options array to safe config array to prevent errors.
        // @see https://www.drupal.org/node/2297311
        if (preg_match('/_options$/', $name)) {
          $this->configuration[$name] = WebformOptionsHelper::encodeConfig($values[$name]);
        }
        else {
          $this->configuration[$name] = $values[$name];
        }
      }
    }

    // Cast debug.
    $this->configuration['debug'] = (bool) $this->configuration['debug'];
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $state = $webform_submission->getWebform()->getSetting('results_disabled') ? WebformSubmissionInterface::STATE_COMPLETED : $webform_submission->getState();
    if ($this->configuration['states'] && in_array($state, $this->configuration['states'])) {
      $message = $this->getMessage($webform_submission);
      $this->sendMessage($webform_submission, $message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postDelete(WebformSubmissionInterface $webform_submission) {
    if (in_array(WebformSubmissionInterface::STATE_DELETED, $this->configuration['states'])) {
      $message = $this->getMessage($webform_submission);
      $this->sendMessage($webform_submission, $message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage(WebformSubmissionInterface $webform_submission) {
    $theme_name = $this->configuration['theme_name'];

    // Switch to custom or default theme.
    $this->themeManager->setCurrentTheme($theme_name);

    $token_options = [
      'email' => TRUE,
      'excluded_elements' => $this->configuration['excluded_elements'],
      'ignore_access' => $this->configuration['ignore_access'],
      'exclude_empty' => $this->configuration['exclude_empty'],
      'exclude_empty_checkbox' => $this->configuration['exclude_empty_checkbox'],
      'exclude_attachments' => $this->configuration['exclude_attachments'],
      'html' => ($this->configuration['html'] && $this->supportsHtml()),
    ];

    $token_data = [];

    $message = [];

    // Copy configuration to $message.
    foreach ($this->configuration as $configuration_key => $configuration_value) {
      // Get configuration name (to, cc, bcc, from, name, subject, mail)
      // and type (mail, options, or text).
      list($configuration_name, $configuration_type) = (strpos($configuration_key, '_') !== FALSE) ? explode('_', $configuration_key) : [$configuration_key, 'text'];

      // Set options and continue.
      if ($configuration_type == 'options') {
        $message[$configuration_key] = $configuration_value;
        continue;
      }

      // Determine if configuration value set to '_default'.
      $is_default_configuration = ($configuration_value === static::DEFAULT_VALUE);
      // Determine if configuration value should use global configuration.
      $is_global_configuration = in_array($configuration_key, ['reply_to', 'return_path', 'sender_mail', 'sender_name']);
      if ($is_default_configuration || (!$configuration_value && $is_global_configuration)) {
        $configuration_value = $this->getDefaultConfigurationValue($configuration_key);
      }

      // Set email addresses.
      if ($configuration_type == 'mail') {
        $emails = $this->getMessageEmails($webform_submission, $configuration_name, $configuration_value);
        $configuration_value = implode(',', array_unique($emails));
      }

      // If Twig enabled render and body, render the Twig template.
      if ($configuration_key == 'body' && $this->configuration['twig']) {
        $message[$configuration_key] = WebformTwigExtension::renderTwigTemplate($webform_submission, $configuration_value, $token_options);
      }
      else {
        // Clear tokens from email values.
        $token_options['clear'] = (strpos($configuration_key, '_mail') !== FALSE) ? TRUE : FALSE;

        // Get replace token values.
        $token_value = $this->replaceTokens($configuration_value, $webform_submission, $token_data, $token_options);

        // Decode entities for all message values except the HTML message body.
        if (!empty($token_value) && is_string($token_value) && !($token_options['html'] && $configuration_key === 'body')) {
          $token_value = Html::decodeEntities($token_value);
        }

        $message[$configuration_key] = $token_value;
      }
    }

    // Trim the message body.
    $message['body'] = trim($message['body']);

    // Convert message body to HTML.
    if ($this->configuration['html'] && $this->supportsHtml()) {
      // Apply optional global format to body.
      // NOTE: $message['body'] is not passed-thru Xss::filter() to allow
      // style tags to be supported.
      if ($format = $this->configFactory->get('webform.settings')->get('html_editor.mail_format')) {
        $build = [
          '#type' => 'processed_text',
          '#text' => $message['body'],
          '#format' => $format,
        ];
        $message['body'] = $this->themeManager->renderPlain($build);
      }
    }

    // Add attachments.
    $message['attachments'] = $this->getMessageAttachments($webform_submission);

    // Add webform submission.
    $message['webform_submission'] = $webform_submission;

    // Add handler.
    $message['handler'] = $this;

    // Switch back to active theme.
    $this->themeManager->setActiveTheme();

    return $message;
  }

  /**
   * Get message to, cc, bcc, and from email addresses.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param string $configuration_name
   *   The email configuration name. (i.e. to, cc, bcc, or from)
   * @param string $configuration_value
   *   The email configuration value.
   *
   * @return array
   *   An array of email addresses and/or tokens.
   */
  protected function getMessageEmails(WebformSubmissionInterface $webform_submission, $configuration_name, $configuration_value) {
    $emails = [];

    // Get element from token and make sure the element has #options.
    $element_name = $this->getElementKeyFromToken($configuration_value);
    $element = ($element_name) ? $this->webform->getElement($element_name) : NULL;
    $element_has_options = ($element && isset($element['#options'])) ? TRUE : FALSE;

    // Check that email handle configuration has email #options.
    $email_has_options = (!empty($this->configuration[$configuration_name . '_options'])) ? TRUE : FALSE;

    // Get emails from options.
    if ($element_has_options && $email_has_options) {
      $email_options = WebformOptionsHelper::decodeConfig($this->configuration[$configuration_name . '_options']);

      // Set default email address.
      if (!empty($email_options[self::DEFAULT_OPTION])) {
        $emails[] = $email_options[self::DEFAULT_OPTION];
      }

      // Get submission email addresses as an array.
      $options_element_value = $webform_submission->getElementData($element_name);
      if (is_array($options_element_value)) {
        $options_values = $options_element_value;
      }
      elseif ($options_element_value) {
        $options_values = [$options_element_value];
      }

      // Set empty email address.
      if (empty($options_values)) {
        if (!empty($email_options[self::EMPTY_OPTION])) {
          $emails[] = $email_options[self::EMPTY_OPTION];
        }
      }
      // Loop through options values and collect email addresses.
      else {
        foreach ($options_values as $option_value) {
          if (!empty($email_options[$option_value])) {
            $emails[] = $email_options[$option_value];
          }
          // Set other email address.
          elseif (!empty($email_options[self::OTHER_OPTION])) {
            $emails[] = $email_options[self::OTHER_OPTION];
          }
        }
      }
    }
    else {
      $emails[] = $configuration_value;
    }

    // Implode unique emails and tokens.
    $emails = implode(',', array_unique($emails));

    // Add user role email addresses to 'To', 'CC', and 'BCC'.
    // IMPORTANT: This is the only place where user email addresses can be
    // used as tokens. This prevents the webform module from being used to
    // spam users or worse… expose user email addresses to malicious users.
    if (in_array($configuration_name, ['to', 'cc', 'bcc'])) {
      $roles = $this->configFactory->get('webform.settings')->get('mail.roles');
      $token_data = [];
      $token_data['webform_role'] = $roles;
      if ($this->moduleHandler->moduleExists('webform_access')) {
        $token_data['webform_access'] = $webform_submission;
      }
      if ($this->moduleHandler->moduleExists('webform_group')) {
        $token_data['webform_group'] = $webform_submission;
      }
      $emails = $this->replaceTokens($emails, $webform_submission, $token_data);
    }

    // Resplit emails to make sure that emails are unique.
    $emails = preg_split('/\s*,\s*/', $emails);
    // Remove all empty email addresses.
    $emails = array_filter($emails);
    // Make sure all email addresses are unique.
    $emails = array_unique($emails);
    // Sort email addresses to make it easier to debug queuing and/or sending
    // issues.
    asort($emails);

    return $emails;
  }

  /**
   * Get message file attachments.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return array
   *   A array of file attachments.
   */
  protected function getMessageAttachments(WebformSubmissionInterface $webform_submission) {
    if (empty($this->configuration['attachments']) || !$this->supportsAttachments()) {
      return [];
    }

    $attachments = [];
    $elements = $this->getWebform()->getElementsInitializedAndFlattened();
    $element_attachments = $this->getWebform()->getElementsAttachments();
    foreach ($element_attachments as $element_attachment) {
      // Check if the element attachment key is excluded and should not attach any files.
      if (isset($this->configuration['excluded_elements'][$element_attachment])) {
        continue;
      }

      $element = $elements[$element_attachment];
      /** @var \Drupal\webform\Plugin\WebformElementAttachmentInterface $element_plugin */
      $element_plugin = $this->elementManager->getElementInstance($element);
      $attachments = array_merge($attachments, $element_plugin->getAttachments($element, $webform_submission));
    }
    return $attachments;
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessage(WebformSubmissionInterface $webform_submission, array $message) {
    $to = $message['to_mail'];
    $from = $message['from_mail'];

    // Remove less than (<) and greater (>) than from name.
    // @todo Figure out the proper way to encode special characters.
    // Note: PhpMail call.
    $message['from_name'] = preg_replace('/[<>]/', '', $message['from_name']);

    if (!empty($message['from_name'])) {
      $from = Mail::formatDisplayName($message['from_name']) . ' <' . $from . '>';
    }

    $current_langcode = $this->languageManager->getCurrentLanguage()->getId();

    // Don't send the message if To, CC, and BCC is empty.
    if (!$this->hasRecipient($webform_submission, $message)) {
      if ($this->configuration['debug']) {
        $t_args = [
          '%form' => $this->getWebform()->label(),
          '%handler' => $this->label(),
        ];
        $this->messenger()->addWarning($this->t('%form: Email not sent for %handler handler because a <em>To</em>, <em>CC</em>, or <em>BCC</em> email was not provided.', $t_args), TRUE);
      }
      return;
    }

    // Render body using webform email message (wrapper) template.
    $build = [
      '#theme' => 'webform_email_message_' . (($this->configuration['html']) ? 'html' : 'text'),
      '#message' => [
        'body' => is_string($message['body']) ? Markup::create($message['body']) : $message['body'],
      ] + $message,
      '#webform_submission' => $webform_submission,
      '#handler' => $this,
    ];
    $theme_name = $this->configuration['theme_name'];
    $message['body'] = trim((string) $this->themeManager->renderPlain($build, $theme_name));

    if ($this->configuration['html']) {
      switch ($this->getMailSystemFormatter()) {
        case 'swiftmailer':
          // SwiftMailer requires that the body be valid Markup.
          $message['body'] = Markup::create($message['body']);
          break;
      }
    }

    // Send message.
    $key = $this->getWebform()->id() . '_' . $this->getHandlerId();

    // Remove webform_submission and handler to prevent memory limit
    // issues during testing.
    if (drupal_valid_test_ua()) {
      unset($message['webform_submission'], $message['handler']);
    }

    // Append additional custom parameters.
    if (!empty($this->configuration['parameters'])) {
      $message += $this->replaceTokens($this->configuration['parameters'], $webform_submission);
    }
    // Remove parameters.
    unset($message['parameters']);

    $result = $this->mailManager->mail('webform', $key, $to, $current_langcode, $message, $from);

    if ($webform_submission->getWebform()->hasSubmissionLog()) {
      // Log detailed message to the 'webform_submission' log.
      $context = [
        '@from_name' => $message['from_name'],
        '@from_mail' => $message['from_mail'],
        '@to_mail' => $message['to_mail'],
        '@subject' => $message['subject'],
        'link' => ($webform_submission->id()) ? $webform_submission->toLink($this->t('View'))->toString() : NULL,
        'webform_submission' => $webform_submission,
        'handler_id' => $this->getHandlerId(),
        'operation' => 'sent email',
      ];
      $this->getLogger('webform_submission')->notice("'@subject' sent to '@to_mail' from '@from_name' [@from_mail]'.", $context);
    }
    else {
      // Log general message to the 'webform' log.
      $context = [
        '@form' => $this->getWebform()->label(),
        '@title' => $this->label(),
        'link' => $this->getWebform()->toLink($this->t('Edit'), 'handlers')->toString(),
      ];
      $this->getLogger('webform')->notice('@form webform sent @title email.', $context);
    }

    // Debug by displaying send email onscreen.
    if ($this->configuration['debug']) {
      $t_args = [
        '%from_name' => $message['from_name'],
        '%from_mail' => $message['from_mail'],
        '%to_mail' => $message['to_mail'],
        '%subject' => $message['subject'],
      ];
      $this->messenger()->addWarning($this->t("%subject sent to %to_mail from %from_name [%from_mail].", $t_args), TRUE);
      $debug_message = $this->buildDebugMessage($webform_submission, $message);
      $this->messenger()->addWarning($this->themeManager->renderPlain($debug_message), TRUE);
    }

    return $result['send'];
  }

  /**
   * {@inheritdoc}
   */
  public function hasRecipient(WebformSubmissionInterface $webform_submission, array $message) {
    // Don't send the message if To, CC, and BCC is empty.
    if (empty($message['to_mail']) && empty($message['cc_mail']) && empty($message['bcc_mail'])) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resendMessageForm(array $message) {
    $element = [];
    $element['to_mail'] = [
      '#type' => 'webform_email_multiple',
      '#title' => $this->t('To email'),
      '#default_value' => $message['to_mail'],
    ];
    $element['cc_mail'] = [
      '#type' => 'webform_email_multiple',
      '#title' => $this->t('CC email'),
      '#default_value' => $message['cc_mail'],
    ];
    $element['bcc_mail'] = [
      '#type' => 'webform_email_multiple',
      '#title' => $this->t('BCC email'),
      '#default_value' => $message['bcc_mail'],
    ];
    $element['from_divider'] = ['#markup' => '<hr/>'];
    $element['from_mail'] = [
      '#type' => 'webform_email_multiple',
      '#title' => $this->t('From email'),
      '#required' => TRUE,
      '#default_value' => $message['from_mail'],
    ];
    $element['from_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('From name'),
      '#required' => TRUE,
      '#default_value' => $message['from_name'],
    ];
    $element['message_divider'] = ['#markup' => '<hr/>'];
    $element['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $message['subject'],
    ];
    $element['body'] = [
      '#type' => ($message['html']) ? 'webform_html_editor' : 'webform_codemirror',
      '#title' => $this->t('Message'),
      '#required' => TRUE,
      '#default_value' => $message['body'],
    ];
    $element['reply_to'] = [
      '#type' => 'value',
      '#value' => $message['reply_to'],
    ];
    $element['return_path'] = [
      '#type' => 'value',
      '#value' => $message['return_path'],
    ];
    $element['html'] = [
      '#type' => 'value',
      '#value' => $message['html'],
    ];
    $element['attachments'] = [
      '#type' => 'value',
      '#value' => $message['attachments'],
    ];
    if ($message['attachments']) {
      $element['files'] = [
        '#type' => 'item',
        '#title' => $this->t('Attachments'),
      ] + $this->buildAttachments($message['attachments']);
    }
    // Preload HTML Editor and CodeMirror so that they can be properly
    // initialized when loaded via Ajax.
    $element['#attached']['library'][] = 'webform/webform.element.html_editor';
    $element['#attached']['library'][] = 'webform/webform.element.codemirror.text';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessageSummary(array $message) {
    return [
      '#settings' => $message,
    ] + parent::getSummary();
  }

  /**
   * Check that HTML emails are supported.
   *
   * @return bool
   *   TRUE if HTML email is supported.
   */
  protected function supportsHtml() {
    return TRUE;
  }

  /**
   * Check that emailing files as attachments is supported.
   *
   * @return bool
   *   TRUE if emailing files as attachments is supported.
   */
  protected function supportsAttachments() {
    // If 'system.mail.interface.default' is 'test_mail_collector'
    // allow email attachments during testing.
    if ($this->configFactory->get('system.mail')->get('interface.default') == 'test_mail_collector') {
      return TRUE;
    }

    // If webform_test.module is installed and this is a test webform
    // allow email attachments.
    if (strpos($this->getWebform()->id(), 'test_') === 0
      && $this->moduleHandler->moduleExists('webform_test')) {
      return TRUE;
    }

    // The Mail System module, which supports a variety of mail handlers,
    // and the SMTP module support attachments.
    $mailsystem_installed = $this->moduleHandler->moduleExists('mailsystem');
    $smtp_enabled = $this->moduleHandler->moduleExists('smtp')
      && $this->configFactory->get('smtp.settings')->get('smtp_on');
    return $mailsystem_installed || $smtp_enabled;
  }

  /**
   * Build debug message.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $message
   *   An email message.
   *
   * @return array
   *   Debug message.
   */
  protected function buildDebugMessage(WebformSubmissionInterface $webform_submission, array $message) {
    // Title.
    $build = [
      '#type' => 'details',
      '#title' => $this->t('Debug: Email: @title', ['@title' => $this->label()]),
    ];

    // Values.
    $values = [
      'from_name' => $this->t('From name'),
      'from_mail' => $this->t('From mail'),
      'to_mail' => $this->t('To mail'),
      'cc_mail' => $this->t('Cc mail'),
      'bcc_mail' => $this->t('Bcc mail'),
      'reply_to' => $this->t('Reply-to'),
      'return_path' => $this->t('Return path'),
      '---' => '---',
      'subject' => $this->t('Subject'),
    ];
    foreach ($values as $name => $title) {
      if ($title == '---') {
        $build[$name] = ['#markup' => '<hr />'];
      }
      elseif (!empty($message[$name])) {
        $build[$name] = [
          '#type' => 'item',
          '#title' => $title,
          '#markup' => $message[$name],
          '#wrapper_attributes' => ['class' => ['container-inline'], 'style' => 'margin: 0'],
        ];
      }
    }
    // Body.
    $build['body'] = [
      '#type' => 'item',
      '#title' => $this->t('Body'),
      '#markup' => Markup::create('<pre>' . htmlentities($message['body']) . '</pre>'),
      '#wrapper_attributes' => ['style' => 'margin: 0'],
    ];
    // Attachments.
    if (!empty($message['attachments'])) {
      $build['attachments_divider'] = ['#markup' => '<hr />'];
      $build['attachments'] = [
        '#type' => 'item',
        '#title' => $this->t('Attachments'),
        '#wrapper_attributes' => ['style' => 'margin: 0'],
        'files' => $this->buildAttachments($message['attachments']),
      ];
    }
    return $build;
  }

  /**
   * Get the Mail System's formatter module name.
   *
   * @return string
   *   The Mail System's formatter module name.
   */
  protected function getMailSystemFormatter() {
    $mailsystem_config = $this->configFactory->get('mailsystem.settings');
    // Get the default formatter.
    $mailsystem_formatter = $mailsystem_config->get('defaults.formatter');
    // Look for a global setting for the webform module.
    $mailsystem_formatter = $mailsystem_config->get('modules.webform.none.formatter') ?: $mailsystem_formatter;
    // Look for a specific setting for this webform module's email.
    $key = 'email_' . $this->getHandlerId();
    $mailsystem_formatter = $mailsystem_config->get("modules.webform.$key.formatter") ?: $mailsystem_formatter;
    return $mailsystem_formatter;
  }

  /**
   * Get message body default values, which can be formatted as text or html.
   *
   * @param string $format
   *   If a format (text or html) is provided the default value for the
   *   specified format is return. If no format is specified an associative
   *   array containing the text and html default body values will be returned.
   *
   * @return string|array
   *   A single (text or html) default body value or an associative array
   *   containing both the text and html default body values.
   */
  protected function getBodyDefaultValues($format = NULL) {
    $webform_settings = $this->configFactory->get('webform.settings');
    $formats = [
      'text' => $webform_settings->get('mail.default_body_text') ?: '[webform_submission:values]',
      'html' => $webform_settings->get('mail.default_body_html') ?: '[webform_submission:values]',
    ];
    return ($format === NULL) ? $formats : $formats[$format];
  }

  /**
   * Build A select other element for email address and names.
   *
   * @param string $name
   *   The element's key.
   * @param string $title
   *   The element's title.
   * @param string $label
   *   The element's label.
   * @param bool $required
   *   TRUE if the element is required.
   * @param array $element_options
   *   The element options.
   * @param array $options_options
   *   The options options.
   * @param array $role_options
   *   The (user) role options.
   * @param array $other_options
   *   The other options.
   *
   * @return array
   *   A select other element.
   */
  protected function buildElement($name, $title, $label, $required = FALSE, array $element_options, array $options_options = NULL, array $role_options = NULL, array $other_options = NULL) {
    list($element_name, $element_type) = (strpos($name, '_') !== FALSE) ? explode('_', $name) : [$name, 'text'];

    $default_option = $this->getDefaultConfigurationValue($name);

    $options = [];
    $options[WebformSelectOther::OTHER_OPTION] = $this->t('Custom @label…', ['@label' => $label]);
    if ($default_option) {
      $options[(string) $this->t('Default')] = [static::DEFAULT_VALUE => $default_option];
    }
    if ($element_options) {
      $options[(string) $this->t('Elements')] = $element_options;
    }
    if ($options_options) {
      $options[(string) $this->t('Options')] = $options_options;
    }
    if ($role_options) {
      $options[(string) $this->t('Roles')] = $role_options;
    }
    if ($other_options) {
      $options[(string) $this->t('Other')] = $other_options;
    }

    $element = [];

    $element[$name] = [
      '#type' => 'webform_select_other',
      '#title' => $title,
      '#options' => $options,
      '#empty_option' => (!$required) ? $this->t('- None -') : NULL,
      '#other__title' => $title,
      '#other__title_display' => 'invisible',
      '#other__placeholder' => $this->t('Enter @label…', ['@label' => $label]),
      '#other__type' => ($element_type == 'mail') ? 'webform_email_multiple' : 'textfield',
      '#other__allow_tokens' => TRUE,
      '#required' => $required,
      '#default_value' => $this->configuration[$name],
    ];

    // Set empty option.
    if (in_array($name, ['reply_to', 'return_path', 'sender_mail', 'sender_name'])) {
      $element[$name]['#empty_option'] = $this->t('- Default -');
    }

    // Remove maxlength.
    if (in_array($name, ['subject'])) {
      $element[$name]['#other__maxlength'] = NULL;
    }

    // Use multiple email for reply_to, return_path, and sender_mail because
    // it supports tokens.
    if (in_array($name, ['reply_to', 'return_path', 'sender_mail'])) {
      $element[$name]['#other__type'] = 'webform_email_multiple';
      $element[$name]['#other__cardinality'] = 1;
      $element[$name]['#other__description'] = '';
      switch ($name) {
        case 'reply_to':
          $element[$name]['#description'] = $this->t('The email address that a recipient will see when they replying to an email.');
          break;

        case 'return_path':
          $element[$name]['#description'] = $this->t('The email address to which bounce messages are delivered.');
          break;

        case 'sender_mail':
          $element[$name]['#description'] = $this->t('The email address submitting the message, if other than shown by the From header');
          break;
      }
      $t_args = ['@title' => $title];
      if ($default_email = $this->getDefaultConfigurationValue($name)) {
        $t_args['%email'] = $default_email;
        $element[$name]['#description'] .= ' ' . $this->t("Leave blank to use %email as the '@title' email.", $t_args);
      }
      else {
        $element[$name]['#description'] .= ' ' . $this->t("Leave blank to automatically use the 'From' address.", $t_args);
      }
    }

    // If no options options are defined return the element.
    if (!$options_options) {
      return $element;
    }

    $ajax_id = 'webform-email-handler-' . $name;
    $this->buildAjaxElementTrigger($ajax_id, $element[$name]);
    $this->buildAjaxElementUpdate($ajax_id, $element);

    // Get options name.
    $options_name = $element_name . '_options';

    if (isset($options_options[$this->configuration[$name]]) && ($token_element_name = $this->getElementKeyFromToken($this->configuration[$name]))) {
      // Get options name and element.
      $options_element = $this->webform->getElement($token_element_name);

      // Set mapping options.
      $mapping_options = $options_element['#options'];
      array_walk($mapping_options, function (&$value, $key) {
        $value = '<b>' . $value . '</b>';
      });
      if (preg_match('/_other$/', $options_element['#type'])) {
        $mapping_options[self::OTHER_OPTION] = $this->t("Other (Used when 'other' value is entered)");
      }
      if (empty($options_element['#required'])) {
        $mapping_options[self::EMPTY_OPTION] = $this->t('Empty (Used when no option is selected)');
      }
      $mapping_options[self::DEFAULT_OPTION] = $this->t('Default (This email address will always be included)');

      // Set placeholder emails.
      $destination_placeholder_emails = ['example@example.com', '[site:mail]'];
      if ($role_options) {
        $role_names = array_keys($role_options);
        $destination_placeholder_emails[] = ($role_names[0] === '[webform_role:authenticated]' && isset($role_names[1])) ? $role_names[1] : $role_names[0];
      }
      $element[$options_name] = [
        '#type' => 'webform_mapping',
        '#title' => $this->t('@title options', ['@title' => $title]),
        '#description' => $this->t('The selected element has multiple options. You may enter email addresses for each choice. When that choice is selected, an email will be sent to the corresponding addresses. If a field is left blank, no email will be sent for that option. You may use tokens.') . '<br /><br />',
        '#description_display' => 'before',
        '#required' => TRUE,
        '#default_value' => WebformOptionsHelper::decodeConfig($this->configuration[$options_name]),

        '#source' => $mapping_options,
        '#source__title' => $this->t('Option'),

        '#destination__type' => 'webform_email_multiple',
        '#destination__allow_tokens' => TRUE,
        '#destination__title' => $this->t('Email addresses'),
        '#destination__description' => NULL,
        '#destination__placeholder' => implode(', ', $destination_placeholder_emails),
      ];
    }
    else {
      $element[$options_name] = [
        '#type' => 'value',
        '#value' => [],
      ];
    }

    $this->buildAjaxElementWrapper($ajax_id, $element[$options_name]);

    return $element;
  }

  /**
   * Build attachment to be displayed via debug message and resend form.
   *
   * @param array $attachments
   *   An array of email attachments.
   *
   * @return array
   *   A renderable array containing links to attachments.
   */
  protected function buildAttachments(array $attachments) {
    $build = [];
    foreach ($attachments as $attachment) {
      $t_args = [
        '@filename' => $attachment['filename'],
        '@filemime' => $attachment['filemime'],
        '@filesize' => format_size(mb_strlen($attachment['filecontent'])),
      ];
      if (!empty($attachment['_fileurl'])) {
        $t_args[':href'] = $attachment['_fileurl'];
        $build[] = ['#markup' => $this->t('<strong><a href=":href">@filename</a></strong> (@filemime) - @filesize ', $t_args)];
      }
      else {
        $build[] = ['#markup' => $this->t('<strong>@filename</strong> (@filemime) - @filesize ', $t_args)];
      }
    }
    return $build;
  }

  /**
   * Get element key from webform token.
   *
   * @param string $token
   *   The token.
   * @param string $format
   *   The element format.
   *
   * @return string|null
   *   The element key or NULL if token can not be parsed.
   */
  protected function getElementKeyFromToken($token, $format = 'raw') {
    if (preg_match('/^\[webform_submission:values:([^:]+):' . $format . '\]$/', $token, $match)) {
      return $match[1];
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function buildTokenTreeElement(array $token_types = ['webform', 'webform_submission'], $description = NULL) {
    $description = $description ?: $this->t('Use [webform_submission:values:ELEMENT_KEY:raw] to get plain text values.');
    return parent::buildTokenTreeElement($token_types, $description);
  }

}
