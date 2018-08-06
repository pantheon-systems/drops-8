<?php

namespace Drupal\webform\Form\AdminConfig;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure webform admin settings for submissions.
 */
class WebformAdminConfigSubmissionsForm extends WebformAdminConfigBaseForm {

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
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, WebformTokenManagerInterface $token_manager) {
    parent::__construct($config_factory);
    $this->tokenManager = $token_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
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
      '#title' => $this->t('Submission settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['submission_settings']['default_submission_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default submission label'),
      '#required' => TRUE,
      '#default_value' => $settings['default_submission_label'],
    ];
    $form['submission_settings']['token_tree_link'] = $this->tokenManager->buildTreeLink();

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
        'description' => $this->t('If checked, all submission events will be logged to dedicated submission log available to all webforms and submissions.'),
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

    // Submission limits.
    $form['submission_limits'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission limits'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['submission_limits']['default_limit_total_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default total submissions limit message'),
      '#required' => TRUE,
      '#default_value' => $config->get('settings.default_limit_total_message'),
    ];
    $form['submission_limits']['default_limit_user_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default per user submission limit message'),
      '#required' => TRUE,
      '#default_value' => $config->get('settings.default_limit_user_message'),
    ];
    $form['submission_limits']['token_tree_link'] = $this->tokenManager->buildTreeLink();

    // Submission purging.
    $form['purge'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission purging'),
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

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $form_state->getValue('submission_settings')
      + $form_state->getValue('submission_behaviors')
      + $form_state->getValue('submission_limits');

    $config = $this->config('webform.settings');
    $config->set('settings', $settings + $config->get('settings'));
    $config->set('purge', $form_state->getValue('purge'));
    $config->save();

    parent::submitForm($form, $form_state);
  }
  
}
