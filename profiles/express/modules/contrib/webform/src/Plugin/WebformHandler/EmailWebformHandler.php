<?php

namespace Drupal\webform\Plugin\WebformHandler;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Xss;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\Entity\File;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Element\WebformSelectOther;
use Drupal\webform\Plugin\WebformElement\WebformManagedFileBase;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformOptionsHelper;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\Plugin\WebformHandlerMessageInterface;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
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
 * )
 */
class EmailWebformHandler extends WebformHandlerBase implements WebformHandlerMessageInterface {

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
   */
  const DEFAULT_OPTION = '_default_';

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, WebformSubmissionConditionsValidatorInterface $conditions_validator, AccountInterface $current_user, MailManagerInterface $mail_manager, WebformTokenManagerInterface $token_manager, WebformElementManagerInterface $element_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger_factory, $config_factory, $entity_type_manager, $conditions_validator);
    $this->currentUser = $current_user;
    $this->mailManager = $mail_manager;
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
      $container->get('plugin.manager.mail'),
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
        $value = preg_replace('/\[webform_submission:(?:node|source_entity|values):([^]]+)\]/', '[\1]', $value);
        $value = preg_replace('/\[webform_submission:([^]]+)\]/', '[\1]', $value);
        $value = preg_replace('/(:raw|:value)(:html)?\]/', ']', $value);
      }
    });

    $states = [
      WebformSubmissionInterface::STATE_DRAFT => $this->t('Draft Saved'),
      WebformSubmissionInterface::STATE_CONVERTED => $this->t('Converted'),
      WebformSubmissionInterface::STATE_COMPLETED => $this->t('Completed'),
      WebformSubmissionInterface::STATE_UPDATED => $this->t('Updated'),
      WebformSubmissionInterface::STATE_DELETED => $this->t('Deleted'),
    ];
    $settings['states'] = array_intersect_key($states, array_combine($settings['states'], $settings['states']));

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
      'to_mail' => 'default',
      'to_options' => [],
      'cc_mail' => '',
      'cc_options' => [],
      'bcc_mail' => '',
      'bcc_options' => [],
      'from_mail' => 'default',
      'from_options' => [],
      'from_name' => 'default',
      'subject' => 'default',
      'body' => 'default',
      'excluded_elements' => [],
      'ignore_access' => FALSE,
      'exclude_empty' => TRUE,
      'html' => TRUE,
      'attachments' => FALSE,
      'debug' => FALSE,
      'reply_to' => '',
      'return_path' => '',
      'sender_mail' => '',
      'sender_name' => '',
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
   *   An associative array containing email configuration values.
   */
  protected function getEmailConfiguration() {
    $configuration = $this->getConfiguration();
    $email = [];
    foreach ($configuration['settings'] as $key => $value) {
      $email[$key] = ($value === 'default') ? $this->getDefaultConfigurationValue($key) : $value;
    }
    return $email;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $this->applyFormStateToConfiguration($form_state);

    // Get options, mail, and text elements as options (text/value).
    $options_element_options = [];
    $mail_element_options = [];
    $text_element_options_value = [];
    $text_element_options_raw = [];
    $elements = $this->webform->getElementsInitializedAndFlattened();
    foreach ($elements as $key => $element) {
      $element_plugin = $this->elementManager->getElementInstance($element);
      if (!$element_plugin->isInput($element)) {
        continue;
      }

      $title = (isset($element['#title'])) ? new FormattableMarkup('@title (@key)', ['@title' => $element['#title'], '@key' => $key]) : $key;
      $text_element_options_value["[webform_submission:values:$key:value]"] = $title;
      $text_element_options_raw["[webform_submission:values:$key:raw]"] = $title;
      if (isset($element['#options'])) {
        $options_element_options["[webform_submission:values:$key:raw]"] = $title;
      }
      elseif (isset($element['#type']) && in_array($element['#type'], ['email', 'hidden', 'value', 'select', 'radios', 'textfield', 'webform_email_multiple', 'webform_email_confirm'])) {
        $mail_element_options["[webform_submission:values:$key:raw]"] = $title;
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
    $form['to']['to_mail'] = $this->buildElement('to_mail', $this->t('To email'), $this->t('To email address'), $mail_element_options, $options_element_options, $roles_element_options, TRUE);
    $form['to']['cc_mail'] = $this->buildElement('cc_mail', $this->t('CC email'), $this->t('CC email address'), $mail_element_options, $options_element_options, $roles_element_options, FALSE);
    $form['to']['bcc_mail'] = $this->buildElement('bcc_mail', $this->t('BCC email'), $this->t('BCC email address'), $mail_element_options, $options_element_options, $roles_element_options, FALSE);
    $token_types = ['webform', 'webform_submission'];
    // Show webform role tokens if they have been specified.
    if (!empty($roles_element_options)) {
      $token_types[] = 'webform_role';
    }

    $form['to']['token_tree_link'] = $this->tokenManager->buildTreeLink(
      $token_types,
      $this->t('Use [webform_submission:values:ELEMENT_NAME:raw] to get plain text values.')
    );

    if (empty($roles_element_options) && $this->currentUser->hasPermission('administer webform')) {
      $form['to']['roles_message'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => $this->t('Please note: You can select which user roles can be available to receive webform emails by going to the Webform module\'s <a href=":href">admin settings</a> form.', [':href' => Url::fromRoute('webform.config.handlers')->toString()]),
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
    $form['from']['from_mail'] = $this->buildElement('from_mail', $this->t('From email'), $this->t('From email address'), $mail_element_options, $options_element_options, NULL, TRUE);
    $form['from']['from_name'] = $this->buildElement('from_name', $this->t('From name'), $this->t('From name'), $text_element_options_raw);
    $form['from']['token_tree_link'] = $this->tokenManager->buildTreeLink(
        ['webform', 'webform_submission'],
        $this->t('Use [webform_submission:values:ELEMENT_NAME:raw] to get plain text values.')
    );

    // Message.
    $form['message'] = [
      '#type' => 'details',
      '#title' => $this->t('Message'),
      '#open' => TRUE,
    ];
    $form['message'] += $this->buildElement('subject', $this->t('Subject'), $this->t('subject'), $text_element_options_raw);

    // Message: Body.
    // Building a custom select other element that toggles between
    // HTML (CKEditor) and Plain text (CodeMirror) custom body elements.
    $body_options = [
      WebformSelectOther::OTHER_OPTION => $this->t('Custom body...'),
      'default' => $this->t('Default'),
      (string) $this->t('Elements') => $text_element_options_value,
    ];
    $body_default_format = ($this->configuration['html']) ? 'html' : 'text';
    $body_default_values = $this->getBodyDefaultValues();
    if (WebformOptionsHelper::hasOption($this->configuration['body'], $body_options)) {
      $body_default_value = $this->configuration['body'];
      $body_custom_default_value = $body_default_values[$body_default_format];
    }
    else {
      $body_default_value = WebformSelectOther::OTHER_OPTION;
      $body_custom_default_value = $this->configuration['body'];
    }
    $form['message']['body'] = [
      '#type' => 'select',
      '#title' => $this->t('Body'),
      '#options' => $body_options,
      '#required' => TRUE,
      '#parents' => ['settings', 'body'],
      '#default_value' => $body_default_value,
    ];
    foreach ($body_default_values as $format => $default_value) {
      // Custom body.
      $custom_default_value = ($format === $body_default_format) ? $body_custom_default_value : $default_value;
      if ($format == 'html') {
        $form['message']['body_custom_' . $format] = [
          '#type' => 'webform_html_editor',
        ];
      }
      else {
        $form['message']['body_custom_' . $format] = [
          '#type' => 'webform_codemirror',
          '#mode' => $format,
        ];
      }
      $form['message']['body_custom_' . $format] += [
        '#title' => $this->t('Body custom value (@format)', ['@label' => $format]),
        '#title_display' => 'hidden',
        '#parents' => ['settings', 'body_custom_' . $format],
        '#default_value' => $custom_default_value,
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

      // Default body.
      $form['message']['body_default_' . $format] = [
        '#type' => 'webform_codemirror',
        '#mode' => $format,
        '#title' => $this->t('Body default value (@format)', ['@label' => $format]),
        '#title_display' => 'hidden',
        '#default_value' => $default_value,
        '#attributes' => ['readonly' => 'readonly', 'disabled' => 'disabled'],
        '#states' => [
          'visible' => [
            ':input[name="settings[body]"]' => ['value' => 'default'],
            ':input[name="settings[html]"]' => ['checked' => ($format == 'html') ? TRUE : FALSE],
          ],
        ],
      ];
    }
    $form['message']['token_tree_link'] = $this->tokenManager->buildTreeLink(
      ['webform', 'webform_submission'],
      $this->t('Use [webform_submission:values:ELEMENT_NAME:raw] to get plain text values and use [webform_submission:values:ELEMENT_NAME:value] to get HTML values.')
    );

    // Elements.
    $form['elements'] = [
      '#type' => 'details',
      '#title' => $this->t('Included email values'),
      '#description' => $this->t('The selected elements will be included in the [webform_submission:values] token. Individual values may still be printed if explicitly specified as a [webform_submission:values:?] in the email body template.'),
      '#open' => $this->configuration['excluded_elements'] ? TRUE : FALSE,
    ];
    $form['elements']['excluded_elements'] = [
      '#type' => 'webform_excluded_elements',
      '#webform_id' => $this->webform->id(),
      '#default_value' => $this->configuration['excluded_elements'],
      '#parents' => ['settings', 'excluded_elements'],
    ];
    $form['elements']['ignore_access'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always include private and restricted access elements.'),
      '#description' => $this->t('If checked, access controls for included element will be ignored.'),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['ignore_access'],
      '#parents' => ['settings', 'ignore_access'],
    ];
    $form['elements']['exclude_empty'] = [
      '#type' => 'checkbox',
      '#title' => t('Exclude empty elements'),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['exclude_empty'],
      '#parents' => ['settings', 'exclude_empty'],
    ];

    $elements = $this->webform->getElementsInitializedFlattenedAndHasValue();
    foreach ($elements as $key => $element) {
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
        WebformSubmissionInterface::STATE_DRAFT => $this->t('...when <b>draft</b> is saved.'),
        WebformSubmissionInterface::STATE_CONVERTED => $this->t('...when anonymous submission is <b>converted</b> to authenticated.'),
        WebformSubmissionInterface::STATE_COMPLETED => $this->t('...when submission is <b>completed</b>.'),
        WebformSubmissionInterface::STATE_UPDATED => $this->t('...when submission is <b>updated</b>.'),
        WebformSubmissionInterface::STATE_DELETED => $this->t('...when submission is <b>deleted</b>.'),
      ],
      '#required' => TRUE,
      '#access' => $results_disabled ? FALSE : TRUE,
      '#parents' => ['settings', 'states'],
      '#default_value' => $results_disabled ? [WebformSubmissionInterface::STATE_COMPLETED] : $this->configuration['states'],
    ];
    // Settings: Reply-to.
    $form['additional']['reply_to'] = $this->buildElement('reply_to', $this->t('Reply-to email'), $this->t('Reply-to email address'), $mail_element_options, NULL, NULL, FALSE);
    // Settings: Return path.
    $form['additional']['return_path'] = $this->buildElement('return_path', $this->t('Return path '), $this->t('Return path email address'), $mail_element_options, NULL, NULL, FALSE);
    // Settings: Sender mail.
    $form['additional']['sender_mail'] = $this->buildElement('sender_mail', $this->t('Sender email'), $this->t('Sender email address'), $mail_element_options, $options_element_options);
    // Settings: Sender name.
    $form['additional']['sender_name'] = $this->buildElement('sender_name', $this->t('Sender name'), $this->t('Sender name'), $text_element_options_raw);

    // Settings: HTML.
    $form['additional']['html'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send email as HTML'),
      '#return_value' => TRUE,
      '#access' => $this->supportsHtml(),
      '#parents' => ['settings', 'html'],
      '#default_value' => $this->configuration['html'],
    ];
    // Settings: Attachments.
    $form['additional']['attachments'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include files as attachments'),
      '#return_value' => TRUE,
      '#access' => $this->supportsAttachments(),
      '#parents' => ['settings', 'attachments'],
      '#default_value' => $this->configuration['attachments'],
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
      '#parents' => ['settings', 'debug'],
      '#default_value' => $this->configuration['debug'],
    ];

    // ISSUE: TranslatableMarkup is breaking the #ajax.
    // WORKAROUND: Convert all Render/Markup to strings.
    WebformElementHelper::convertRenderMarkupToStrings($form);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    $values = $form_state->getValues();

    // Set custom body based on the selected format.
    if ($values['body'] === WebformSelectOther::OTHER_OPTION) {
      $body_format = ($values['html']) ? 'html' : 'text';
      $values['body'] = $values['body_custom_' . $body_format];
    }
    unset(
      $values['body_custom_text'],
      $values['body_default_html']
    );

    $form_state->setValues($values);
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
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $state = $webform_submission->getWebform()->getSetting('results_disabled') ? WebformSubmissionInterface::STATE_COMPLETED : $webform_submission->getState();
    if (in_array($state, $this->configuration['states'])) {
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
    $token_data = [];
    $token_options = [
      'email' => TRUE,
      'excluded_elements' => $this->configuration['excluded_elements'],
      'ignore_access' => $this->configuration['ignore_access'],
      'exclude_empty' => $this->configuration['exclude_empty'],
      'html' => ($this->configuration['html'] && $this->supportsHtml()),
    ];

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

      // Set default value.
      if ($configuration_value === 'default') {
        $configuration_value = $this->getDefaultConfigurationValue($configuration_key);
      }

      // Set email addresses.
      if ($configuration_type == 'mail') {
        $emails = $this->getMessageEmails($webform_submission, $configuration_name, $configuration_value);
        $configuration_value = implode(',', array_unique($emails));
      }

      // Set message key.
      $message[$configuration_key] = $this->tokenManager->replace($configuration_value, $webform_submission, $token_data, $token_options);
    }

    // Trim the message body.
    $message['body'] = trim($message['body']);

    // Alter body based on the mail system sender.
    if ($this->configuration['html'] && $this->supportsHtml()) {
      $message['body'] = WebformHtmlEditor::checkMarkup($message['body'], TRUE);
    }
    else {
      // Decode HTML entities in plain text body.
      $message['body'] = Html::decodeEntities($message['body']);
    }

    // Add attachments.
    $message['attachments'] = $this->getMessageAttachments($webform_submission);

    // Add webform submission.
    $message['webform_submission'] = $webform_submission;

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
    $element_name = $this->getElementNameFromToken($configuration_value);
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

      // Get submission email addresseses as an array.
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
    // used as tokens.  This prevents the webform module from being used to
    // spam users or worse...expose user email addresses to malicious users.
    if (in_array($configuration_name, ['to', 'cc', 'bcc'])) {
      $roles = $this->configFactory->get('webform.settings')->get('mail.roles');
      $emails = $this->tokenManager->replace($emails, $webform_submission, ['webform_role' => $roles]);
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
    $elements = $this->webform->getElementsInitializedAndFlattened();
    foreach ($elements as $configuration_key => $element) {
      $element_plugin = $this->elementManager->getElementInstance($element);
      // Only elements that extend the 'Managed file' element can add
      // file attachments.
      if (!($element_plugin instanceof WebformManagedFileBase)) {
        continue;
      }

      // Check if the element is excluded and should not attach any files.
      if (isset($this->configuration['excluded_elements'][$configuration_key])) {
        continue;
      }

      // Get file ids.
      $fids = $webform_submission->getElementData($configuration_key);
      if (empty($fids)) {
        continue;
      }

      /** @var \Drupal\file\FileInterface[] $files */
      $files = File::loadMultiple(is_array($fids) ? $fids : [$fids]);
      foreach ($files as $file) {
        $attachments[] = [
          'filecontent' => file_get_contents($file->getFileUri()),
          'filename' => $file->getFilename(),
          'filemime' => $file->getMimeType(),
          // Add URL to be used by resend webform.
          'file' => $file,
        ];
      }
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
      $from = $message['from_name'] . ' <' . $from . '>';
    }

    $current_langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();

    // Don't send the message if To, CC, and BCC is empty.
    if (!$this->hasRecipient($webform_submission, $message)) {
      if ($this->configuration['debug']) {
        $t_args = [
          '%form' => $this->getWebform()->label(),
          '%handler' => $this->label(),
        ];
        drupal_set_message($this->t('%form: Email not sent for %handler handler because a <em>To</em>, <em>CC</em>, or <em>BCC</em> email was not provided.', $t_args), 'warning', TRUE);
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
    $message['body'] = trim((string) \Drupal::service('renderer')->renderPlain($build));

    if ($this->configuration['html']) {
      switch ($this->getMailSystemSender()) {
        case 'swiftmailer':
          // SwiftMailer requires that the body be valid Markup.
          $message['body'] = Markup::create($message['body']);
          break;
      }
    }

    // Send message.
    $this->mailManager->mail('webform', 'email_' . $this->getHandlerId(), $to, $current_langcode, $message, $from);

    // Log message in Drupal's log.
    $context = [
      '@form' => $this->getWebform()->label(),
      '@title' => $this->label(),
      'link' => $this->getWebform()->toLink($this->t('Edit'), 'handlers')->toString(),
    ];
    $this->getLogger()->notice('@form webform sent @title email.', $context);

    // Log message in Webform's submission log.
    $t_args = [
      '@from_name' => $message['from_name'],
      '@from_mail' => $message['from_mail'],
      '@to_mail' => $message['to_mail'],
      '@subject' => $message['subject'],
    ];
    $this->log($webform_submission, 'sent email', $this->t("'@subject' sent to '@to_mail' from '@from_name' [@from_mail]'.", $t_args));

    // Debug by displaying send email onscreen.
    if ($this->configuration['debug']) {
      $t_args = [
        '%from_name' => $message['from_name'],
        '%from_mail' => $message['from_mail'],
        '%to_mail' => $message['to_mail'],
        '%subject' => $message['subject'],
      ];
      drupal_set_message($this->t("%subject sent to %to_mail from %from_name [%from_mail].", $t_args), 'warning', TRUE);
      $debug_message = $this->buildDebugMessage($webform_submission, $message);
      drupal_set_message(\Drupal::service('renderer')->renderPlain($debug_message), 'warning', TRUE);
    }
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

    // Display attached files.
    if ($message['attachments']) {
      $file_links = [];
      foreach ($message['attachments'] as $attachment) {
        $file_links[] = [
          '#theme' => 'file_link',
          '#file' => $attachment['file'],
          '#prefix' => '<div>',
          '#suffix' => '</div>',
        ];
      }
      $element['files'] = [
        '#type' => 'item',
        '#title' => $this->t('Attachments'),
        '#markup' => \Drupal::service('renderer')->renderPlain($file_links),
      ];
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
    // If 'system.mail.interface.default' is 'test_mail_collector' allow
    // email attachments during testing.
    if (\Drupal::configFactory()->get('system.mail')->get('interface.default') == 'test_mail_collector') {
      return TRUE;
    }
    return \Drupal::moduleHandler()->moduleExists('mailsystem');
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
      '#markup' => ($message['html']) ? $message['body'] : '<pre>' . htmlentities($message['body']) . '</pre>',
      '#allowed_tags' => Xss::getAdminTagList(),
      '#wrapper_attributes' => ['style' => 'margin: 0'],
    ];
    return $build;
  }

  /**
   * Get the Mail System's sender module name.
   *
   * @return string
   *   The Mail System's sender module name.
   */
  protected function getMailSystemSender() {
    $mailsystem_config = $this->configFactory->get('mailsystem.settings');
    // Get the default sender.
    $mailsystem_sender = $mailsystem_config->get('defaults.sender');
    // Look for a global setting for the webform module.
    $mailsystem_sender = $mailsystem_config->get('modules.webform.none.sender') ?: $mailsystem_sender;
    // Look for a specific setting for this webform module's email.
    $key = 'email_' . $this->getHandlerId();
    $mailsystem_sender = $mailsystem_config->get("modules.webform.$key.sender") ?: $mailsystem_sender;
    return $mailsystem_sender;
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
   * Build A select other element for email addresss and names.
   *
   * @param string $name
   *   The element's key.
   * @param string $title
   *   The element's title.
   * @param string $label
   *   The element's label.
   * @param array $element_options
   *   The element options.
   * @param array $options_options
   *   The options options.
   * @param array $role_options
   *   The (user) role options.
   * @param bool $required
   *   TRUE if the element is required.
   *
   * @return array
   *   A select other element.
   */
  protected function buildElement($name, $title, $label, array $element_options, array $options_options = NULL, array $role_options = NULL, $required = FALSE) {
    list($element_name, $element_type) = (strpos($name, '_') !== FALSE) ? explode('_', $name) : [$name, 'text'];

    $options = [];
    $options[WebformSelectOther::OTHER_OPTION] = $this->t('Custom @label...', ['@label' => $label]);
    if ($default_option = $this->getDefaultConfigurationValue($name)) {
      $options[(string) $this->t('Default')] = ['default' => $default_option];
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

    $ajax_wrapper = Html::getUniqueId('ajax-wrapper');

    $element = [
      '#type' => 'container',
      '#attributes' => ['id' => $ajax_wrapper],
    ];

    $element[$name] = [
      '#type' => 'webform_select_other',
      '#title' => $title,
      '#options' => $options,
      '#empty_option' => (!$required) ? '' : NULL,
      '#other__title' => $title,
      '#other__title_display' => 'hidden',
      '#other__placeholder' => $this->t('Enter @label...', ['@label' => $label]),
      '#other__type' => ($element_type == 'mail') ? 'webform_email_multiple' : 'textfield',
      '#other__allow_tokens' => TRUE,
      '#required' => $required,
      '#parents' => ['settings', $name],
      '#default_value' => $this->configuration[$name],
    ];

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

    // Add Ajax trigger update submit button.
    $element[$name]['#attributes']['data-webform-trigger-submit'] = ".js-$ajax_wrapper-submit";

    // Add update submit button.
    $element[$name . '_update'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
      '#name' => $element_name . '_update_button',
      '#validate' => [],
      '#limit_validation_errors' => [$element[$name]['#parents']],
      '#submit' => [[get_called_class(), 'rebuildCallback']],
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxCallback'],
        'wrapper' => $ajax_wrapper,
        'progress' => ['type' => 'fullscreen'],
      ],
      '#attributes' => [
        'class' => [
          'js-hide',
          "js-$ajax_wrapper-submit",
          'js-webform-novalidate',
        ],
      ],
    ];

    // Attached webform.form library for Ajax submit trigger behavior.
    $element['#attached']['library'][] = 'webform/webform.form';

    // Get options name.
    $options_name = $element_name . '_options';

    if (isset($options_options[$this->configuration[$name]]) && ($token_element_name = $this->getElementNameFromToken($this->configuration[$name]))) {
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
      $destination_placeholde_emails = ['example@example.com', '[site:mail]'];
      if ($role_options) {
        $role_names = array_keys($role_options);
        $destination_placeholde_emails[] = ($role_names[0] === '[webform_role:authenticated]' && isset($role_names[1])) ? $role_names[1] : $role_names[0];
      }
      $element[$options_name] = [
        '#type' => 'webform_mapping',
        '#title' => $this->t('@title options', ['@title' => $title]),
        '#description' => $this->t('The selected element has multiple options. You may enter email addresses for each choice. When that choice is selected, an email will be sent to the corresponding addresses. If a field is left blank, no email will be sent for that option. You may use tokens.') . '<br /><br />',
        '#description_display' => 'before',
        '#required' => TRUE,
        '#parents' => ['settings', $options_name],
        '#default_value' => WebformOptionsHelper::decodeConfig($this->configuration[$options_name]),

        '#source' => $mapping_options,
        '#source__title' => $this->t('Option'),

        '#destination__type' => 'webform_email_multiple',
        '#destination__allow_tokens' => TRUE,
        '#destination__title' => $this->t('Email addresses'),
        '#destination__description' => NULL,
        '#destination__placeholder' => implode(', ', $destination_placeholde_emails),
      ];
    }
    else {
      $element[$options_name] = [
        '#type' => 'value',
        '#value' => [],
        '#parents' => ['settings', $options_name],
      ];
    }

    return $element;
  }

  /**
   * Rebuild callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function rebuildCallback(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * Ajax callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   An associative array containing the email options elements.
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state) {
    $trigger_element = $form_state->getTriggeringElement();
    return NestedArray::getValue($form, array_slice($trigger_element['#array_parents'], 0, -1));
  }

  /**
   * Get element name from webform token.
   *
   * @param string $token
   *   The token.
   * @param string $format
   *   The element format.
   *
   * @return string|null
   *   The element name or NULL if token can not be parsed.
   */
  protected function getElementNameFromToken($token, $format = 'raw') {
    if (preg_match('/\[webform_submission:values:([^:]+):' . $format . '\]/', $token, $match)) {
      return $match[1];
    }
    else {
      return NULL;
    }
  }

}
