<?php

namespace Drupal\webform\Form\AdminConfig;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure webform admin settings for submissions.
 */
class WebformAdminConfigSubmissionsForm extends WebformAdminConfigBaseForm {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

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
    return 'webform_admin_config_submissions_form';
  }

  /**
   * Constructs a WebformAdminConfigSubmissionsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, WebformTokenManagerInterface $token_manager) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
    $this->tokenManager = $token_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('webform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('webform.settings');
    $settings = $config->get('settings');

    // Submission settings.
    $form['submission_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission general settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['submission_settings']['default_submission_access_denied_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default access denied message'),
      '#required' => TRUE,
      '#default_value' => $settings['default_submission_access_denied_message'],
    ];
    $form['submission_settings']['default_submission_exception_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default exception message'),
      '#required' => TRUE,
      '#default_value' => $settings['default_submission_exception_message'],
    ];
    $form['submission_settings']['default_submission_locked_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default locked message'),
      '#required' => TRUE,
      '#default_value' => $settings['default_submission_locked_message'],
    ];
    $form['submission_settings']['default_previous_submission_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default previous submission message'),
      '#required' => TRUE,
      '#default_value' => $settings['default_previous_submission_message'],
    ];
    $form['submission_settings']['default_previous_submissions_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default previous submissions message'),
      '#required' => TRUE,
      '#default_value' => $settings['default_previous_submissions_message'],
    ];
    $form['submission_settings']['default_autofill_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default autofill message'),
      '#description' => $this->t('Leave blank to not display a message when a form is autofilled.'),
      '#default_value' => $settings['default_autofill_message'],
    ];
    $form['submission_settings']['default_submission_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default submission label'),
      '#required' => TRUE,
      '#default_value' => $settings['default_submission_label'],
    ];
    $form['submission_settings']['token_tree_link'] = $this->tokenManager->buildTreeElement();

    // Submission Behaviors.
    $form['submission_behaviors'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission behaviors'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $behavior_elements = [
      'default_submission_log' => [
        'title' => $this->t('Log all submission events for all webforms'),
        'description' => $this->t('If checked, all submission events will be logged to dedicated submission log available to all webforms and submissions.') . '<br/><br/>' .
          '<em>' . t('The webform submission log will track more detailed user information including email addresses and subjects.') . '</em>',
      ],
      'default_results_customize' => [
        'title' => $this->t('Allow users to customize the submission results table'),
        'description' => $this->t('If checked, users can individually customize the submission results table for all webforms.'),
      ],
    ];
    foreach ($behavior_elements as $behavior_key => $behavior_element) {
      $form['submission_behaviors'][$behavior_key] = [
        '#type' => 'checkbox',
        '#title' => $behavior_element['title'],
        '#description' => $behavior_element['description'],
        '#return_value' => TRUE,
        '#default_value' => $settings[$behavior_key],
      ];
    }
    if (!$this->moduleHandler->moduleExists('webform_submission_log')) {
      $form['submission_behaviors']['webform_submission_log_message'] = [
        '#type' => 'webform_message',
        '#message_type' => 'info',
        '#message_message' => $this->t("Enable the 'Webform Submission Log' module to better track and permanently store submission logs."),
        '#message_close' => TRUE,
        '#message_storage' => WebformMessage::STORAGE_SESSION,
        '#states' => [
          'visible' => [
            ':input[name="submission_behaviors[default_submission_log]"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    // Submission limits.
    $form['submission_limits'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission limit settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['submission_limits']['default_limit_total_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default total submissions limit message'),
      '#default_value' => $config->get('settings.default_limit_total_message'),
    ];
    $form['submission_limits']['default_limit_user_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default per user submission limit message'),
      '#default_value' => $config->get('settings.default_limit_user_message'),
    ];
    $form['submission_limits']['token_tree_link'] = $this->tokenManager->buildTreeElement();

    // Draft settings.
    $form['draft_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission draft settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['draft_settings']['default_draft_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default draft button label'),
      '#required' => TRUE,
      '#size' => 20,
      '#default_value' => $settings['default_draft_button_label'],
    ];
    $form['draft_settings']['default_draft_pending_single_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default draft pending single draft message'),
      '#default_value' => $settings['default_draft_pending_single_message'],
    ];
    $form['draft_settings']['default_draft_pending_multiple_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default draft pending multiple drafts message'),
      '#default_value' => $settings['default_draft_pending_multiple_message'],
    ];
    $form['draft_settings']['default_draft_saved_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default draft save message'),
      '#default_value' => $settings['default_draft_saved_message'],
    ];
    $form['draft_settings']['default_draft_loaded_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default draft load message'),
      '#default_value' => $settings['default_draft_loaded_message'],
    ];
    $form['draft_settings']['token_tree_link'] = $this->tokenManager->buildTreeElement();

    // Submission purging.
    $form['purge'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission purge settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['purge']['cron_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Amount of submissions to process'),
      '#min' => 1,
      '#default_value' => $config->get('purge.cron_size'),
      '#description' => $this->t('Enter the amount of submissions to be purged during single cron run. You may want to lower this number if you are facing memory or timeout issues when purging via cron.'),
    ];

    // Submission views.
    $form['views_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission views settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['views_settings']['default_submission_views'] = [
      '#type' => 'webform_submission_views',
      '#title' => $this->t('Submission views'),
      '#title_display' => 'invisible',
      '#global' => TRUE,
      '#default_value' => $settings['default_submission_views'],
    ];
    $form['views_settings']['message'] = [
      '#type' => 'webform_message',
      '#message_type' => 'info',
      '#message_message' => $this->t('Uncheck the below settings to allow webform administrators to choose which results should be replaced with submission views.'),
      '#message_close' => TRUE,
      '#message_storage' => WebformMessage::STORAGE_SESSION,
    ];
    $form['views_settings']['default_submission_views_replace'] = [
      '#type' => 'webform_submission_views_replace',
      '#global' => TRUE,
      '#default_value' => $settings['default_submission_views_replace'],
    ];

    $this->tokenManager->elementValidate($form);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $form_state->getValue('submission_settings')
      + $form_state->getValue('submission_behaviors')
      + $form_state->getValue('submission_limits')
      + $form_state->getValue('draft_settings')
      + $form_state->getValue('views_settings');

    // Update config and submit form.
    $config = $this->config('webform.settings');
    $config->set('settings', $settings + $config->get('settings'));
    $config->set('purge', $form_state->getValue('purge'));
    parent::submitForm($form, $form_state);
  }

}
