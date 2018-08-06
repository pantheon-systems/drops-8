<?php

namespace Drupal\webform\Form\AdminConfig;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerManagerInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure webform admin settings for handlers.
 */
class WebformAdminConfigHandlersForm extends WebformAdminConfigBaseForm {

  /**
   * The webform handler manager.
   *
   * @var \Drupal\webform\Plugin\WebformHandlerManagerInterface
   */
  protected $handlerManager;

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_admin_config_handlers_form';
  }

  /**
   * Constructs a WebformAdminConfigHandlersForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\webform\Plugin\WebformHandlerManagerInterface $handler_manager
   *   The webform handler manager.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, WebformHandlerManagerInterface $handler_manager, WebformTokenManagerInterface $token_manager) {
    parent::__construct($config_factory);
    $this->handlerManager = $handler_manager;
    $this->tokenManager = $token_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.webform.handler'),
      $container->get('webform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('webform.settings');

    // Email / Handler: Mail.
    $form['mail'] = [
      '#type' => 'details',
      '#title' => $this->t('Email settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['mail']['roles'] = [
      '#type' => 'webform_roles',
      '#title' => $this->t('Recipent roles'),
      '#description' => $this->t("Select roles that can be assigned to receive a webform's email. <em>Please note: Selected roles will be available to all webforms.</em>"),
      '#include_anonymous' => FALSE,
      '#default_value' => $config->get('mail.roles'),
    ];
    $form['mail']['default_to_mail'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default to email'),
      '#description' => $this->t('The default recipient address for emailed webform results.'),
      '#required' => TRUE,
      '#default_value' => $config->get('mail.default_to_mail'),
    ];
    $form['mail']['default_from_mail'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default from email'),
      '#description' => $this->t('The default sender address for emailed webform results; often the email address of the maintainer of your forms.'),
      '#required' => TRUE,
      '#default_value' => $config->get('mail.default_from_mail'),
    ];
    $form['mail']['default_from_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default from name'),
      '#description' => $this->t('The default sender name which is used along with the default from address.'),
      '#required' => TRUE,
      '#default_value' => $config->get('mail.default_from_name'),
    ];
    $form['mail']['default_reply_to'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default reply-to email'),
      '#description' => $this->t("The default email address that a recipient will see when they are replying to an email. Leave blank to automatically use the 'From email' address. Setting the 'Reply-to' to the 'From email' prevent emails from being flagged as spam."),
      '#default_value' => $config->get('mail.default_reply_to'),
    ];
    $form['mail']['default_return_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default return path (email)'),
      '#description' => $this->t("The default email address to which bounce messages are delivered. Leave blank to automatically use the 'From email' address."),
      '#default_value' => $config->get('mail.default_return_path'),
    ];
    $form['mail']['default_sender_mail'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default sender email'),
      '#description' => $this->t('The default sender address for emailed webform results; often the email address of the maintainer of your forms. The person or agent submitting the message to the network, if other than shown by the From header'),
      '#default_value' => $config->get('mail.default_sender_mail'),
    ];
    $form['mail']['default_sender_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default sender name'),
      '#description' => $this->t('The default sender name which is used along with the default sender email address.'),
      '#default_value' => $config->get('mail.default_sender_name'),
    ];    
    $form['mail']['default_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default email subject'),
      '#required' => TRUE,
      '#default_value' => $config->get('mail.default_subject'),
    ];
    $form['mail']['default_body_text'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'text',
      '#title' => $this->t('Default email body (Plain text)'),
      '#required' => TRUE,
      '#default_value' => $config->get('mail.default_body_text'),
    ];
    $form['mail']['default_body_html'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'html',
      '#title' => $this->t('Default email body (HTML)'),
      '#required' => TRUE,
      '#default_value' => $config->get('mail.default_body_html'),
    ];
    $form['mail']['token_tree_link'] = $this->tokenManager->buildTreeLink();

    // Email / Handler: Types.
    $form['handler_types'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission handlers'),
      '#description' => $this->t('Select available submission handlers'),
      '#open' => TRUE,
    ];
    $form['handler_types']['excluded_handlers'] = $this->buildExcludedPlugins(
      $this->handlerManager,
      $config->get('handler.excluded_handlers')
    );
    $excluded_handler_checkboxes = [];
    foreach ($form['handler_types']['excluded_handlers']['#options'] as $handler_id => $option) {
      if ($excluded_handler_checkboxes) {
        $excluded_handler_checkboxes[] = 'or';
      }
      $excluded_handler_checkboxes[] = [':input[name="excluded_handlers[' . $handler_id . ']"]' => ['checked' => FALSE]];
    }
    $form['handler_types']['excluded_handlers_message'] = [
      '#type' => 'webform_message',
      '#message_message' => $this->t('All excluded handlers must be manually removed from existing webforms.'),
      '#message_type' => 'warning',
      '#states' => [
        'visible' => $excluded_handler_checkboxes,
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $excluded_handlers = $this->convertIncludedToExcludedPluginIds($this->handlerManager, $form_state->getValue('excluded_handlers'));

    $config = $this->config('webform.settings');
    $config->set('handler', ['excluded_handlers' => $excluded_handlers]);
    $config->set('mail', $form_state->getValue('mail'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
