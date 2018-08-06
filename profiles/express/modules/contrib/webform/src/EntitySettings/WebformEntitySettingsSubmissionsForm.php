<?php

namespace Drupal\webform\EntitySettings;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionStorageInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform submissions settings.
 */
class WebformEntitySettingsSubmissionsForm extends WebformEntitySettingsBaseForm {

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * Constructs a WebformEntitySettingsForm.
   *
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   */
  public function __construct(WebformTokenManagerInterface $token_manager) {
    $this->tokenManager = $token_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->entity;

    $elements = $webform->getElementsDecoded();
    $settings = $webform->getSettings();

    // Display warning and disable the submission form.
    $results_enabled = empty($settings['results_disabled']) && empty($elements['#method']);
    if (!$results_enabled) {
      drupal_set_message($this->t('Saving of submissions is disabled, submission settings, submission limits, purging and the saving of drafts is disabled. Submissions must be sent via an email or handled using a <a href=":href">custom webform handler</a>.', [':href' => $webform->toUrl('handlers')->toString()]), 'warning');
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->entity;

    /** @var \Drupal\webform\WebformEntityStorageInterface $webform_storage */
    $webform_storage = $this->entityTypeManager->getStorage('webform');

    /** @var \Drupal\webform\WebformSubmissionStorageInterface $webform_submission_storage */
    $webform_submission_storage = $this->entityTypeManager->getStorage('webform_submission');

    $default_settings = $this->config('webform.settings')->get('settings');
    $settings = $webform->getSettings();

    // Submission settings.
    $form['submission_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission settings'),
      '#open' => TRUE,
    ];
    $form['submission_settings']['submission_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Submission label'),
      '#default_value' => $settings['submission_label'],
    ];
    $form['submission_settings']['next_serial'] = [
      '#type' => 'number',
      '#title' => $this->t('Next submission number'),
      '#description' => $this->t('The value of the next submission number. This is usually 1 when you start and will go up with each webform submission.'),
      '#min' => 1,
      '#default_value' => $webform_storage->getNextSerial($webform),
    ];
    $form['submission_settings']['token_tree_link'] = $this->tokenManager->buildTreeLink();
    $form['submission_settings']['submission_columns'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission columns'),
      '#description' => $this->t('Below columns are displayed to users who can view previous submissions and/or pending drafts.'),
    ];
    // Submission user columns.
    // @see \Drupal\webform\Form\WebformResultsCustomForm::buildForm
    $available_columns = $webform_submission_storage->getColumns($webform);
    // Remove columns that should never be displayed to users.
    $available_columns = array_diff_key($available_columns, array_flip(['uuid', 'in_draft', 'entity', 'sticky', 'notes', 'uid', 'operations']));
    $custom_columns = $webform_submission_storage->getUserColumns($webform);
    // Change sid's # to an actual label.
    $available_columns['sid']['title'] = $this->t('Submission ID');
    if (isset($custom_columns['sid'])) {
      $custom_columns['sid']['title'] = $this->t('Submission ID');
    }
    // Get available columns as option.
    $columns_options = [];
    foreach ($available_columns as $column_name => $column) {
      $title = (strpos($column_name, 'element__') === 0) ? ['data' => ['#markup' => '<b>' . $column['title'] . '</b>']] : $column['title'];
      $key = (isset($column['key'])) ? str_replace('webform_', '', $column['key']) : $column['name'];
      $columns_options[$column_name] = ['title' => $title, 'key' => $key];
    }
    // Get custom columns as the default value.
    $columns_keys = array_keys($custom_columns);
    $columns_default_value = array_combine($columns_keys, $columns_keys);
    // Display columns in sortable table select element.
    $form['submission_settings']['submission_columns']['submission_user_columns'] = [
      '#type' => 'webform_tableselect_sort',
      '#header' => [
        'title' => $this->t('Title'),
        'key' => $this->t('Key'),
      ],
      '#options' => $columns_options,
      '#default_value' => $columns_default_value,
    ];

    // Submission behaviors.
    $form['submission_behaviors'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission behaviors'),
      '#open' => TRUE,
    ];
    $form['submission_behaviors']['form_confidential'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Confidential submissions'),
      '#description' => $this->t('Confidential submissions have no recorded IP address and must be submitted while logged out.'),
      '#return_value' => TRUE,
      '#default_value' => $settings['form_confidential'],
      '#weight' => -100,
    ];
    $form['submission_behaviors']['form_confidential_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Webform confidential message'),
      '#description' => $this->t('A message to be displayed when authenticated users try to access a confidential webform.'),
      '#default_value' => $settings['form_confidential_message'],
      '#states' => [
        'visible' => [
          ':input[name="form_confidential"]' => ['checked' => TRUE],
        ],
      ],
      '#weight' => -99,
    ];
    $form['submission_behaviors']['form_convert_anonymous'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Convert anonymous user drafts and submissions to authenticated user'),
      '#description' => $this->t('If checked, drafts and submissions created by an anonymous user will be reassigned to their user account when they login.'),
      '#return_value' => TRUE,
      '#default_value' => $settings['form_convert_anonymous'],
      '#states' => [
        'visible' => [
          ':input[name="form_confidential"]' => ['checked' => FALSE],
        ],
      ],
      '#weight' => -98,
    ];
    $behavior_elements = [
      // Form specific behaviors.
      'form_previous_submissions' => [
        'title' => $this->t('Show the notification about previous submissions'),
        'form_description' => $this->t('Show the previous submissions notification that appears when users have previously submitted this form.'),
      ],
      'token_update' => [
        'title' => $this->t('Allow users to update a submission using a secure token.'),
        'form_description' => $this->t("If checked users will be able to update a submission using the webform's URL appended with the submission's (secure) token.") . ' ' .
          $this->t("The 'tokenized' URL to update a submission will be available when viewing a submission's information and can be inserted into an email using the [webform_submission:update-url] token.") . ' '  .
          $this->t('Only webforms that are open to new submissions can be updated using the secure token.'),
      ],
      // Global behaviors.
      // @see \Drupal\webform\Form\WebformAdminSettingsForm
      'submission_log' => [
        'title' => $this->t('Log submission events'),
        'all_description' => $this->t('All submission event are being logged for all webforms'),
        'form_description' => $this->t('If checked, events will be logged for submissions to this webforms.'),
      ],
    ];
    $this->appendBehaviors($form['submission_behaviors'], $behavior_elements, $settings, $default_settings);
    $form['submission_behaviors']['token_update_warning'] = [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t("Submissions accessed using the (secure) token will by-pass all webform submission access rules."),
      '#states' => [
        'visible' => [
          ':input[name="token_update"]' => ['checked' => TRUE],
        ],
      ],
      '#weight' => $form['submission_behaviors']['token_update']['#weight'] + 1,
    ];

    // Submission limits.
    $form['submission_limits'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission limits'),
      '#open' => TRUE,
    ];
    $form['submission_limits']['total'] = [
      '#type' => 'details',
      '#title' => $this->t('Total submissions'),
    ];
    $form['submission_limits']['total']['limit_total'] = [
      '#type' => 'number',
      '#title' => $this->t('Total submissions limit'),
      '#min' => 1,
      '#default_value' => $settings['limit_total'],
    ];
    $form['submission_limits']['total']['entity_limit_total'] = [
      '#type' => 'number',
      '#title' => $this->t('Total submissions limit per source entity'),
      '#min' => 1,
      '#default_value' => $settings['entity_limit_total'],
    ];
    $form['submission_limits']['total']['limit_total_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Total submissions limit message'),
      '#min' => 1,
      '#default_value' => $settings['limit_total_message'],
    ];
    $form['submission_limits']['user'] = [
      '#type' => 'details',
      '#title' => $this->t('Per user'),
      '#description' => $this->t('Limit the number of submissions per user. A user is identified by their user id if logged-in, or by their Cookie if anonymous.'),
    ];
    $form['submission_limits']['user']['limit_user'] = [
      '#type' => 'number',
      '#title' => $this->t('Per user submission limit'),
      '#min' => 1,
      '#default_value' => $settings['limit_user'],
    ];
    $form['submission_limits']['user']['entity_limit_user'] = [
      '#type' => 'number',
      '#min' => 1,
      '#title' => $this->t('Per user submission limit per source entity'),
      '#default_value' => $settings['entity_limit_user'],
    ];
    $form['submission_limits']['user']['limit_user_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Per user submission limit message'),
      '#default_value' => $settings['limit_user_message'],
    ];

    // Purge settings.
    $form['purge_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission purging'),
      '#open' => TRUE,
    ];
    $form['purge_settings']['purge'] = [
      '#type' => 'select',
      '#title' => $this->t('Automatically purge'),
      '#options' => [
        WebformSubmissionStorageInterface::PURGE_NONE => $this->t('None'),
        WebformSubmissionStorageInterface::PURGE_DRAFT => $this->t('Draft'),
        WebformSubmissionStorageInterface::PURGE_COMPLETED => $this->t('Completed'),
        WebformSubmissionStorageInterface::PURGE_ALL => $this->t('Draft and completed'),
      ],
      '#default_value' => $settings['purge'],
    ];
    $form['purge_settings']['purge_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Days to retain submissions'),
      '#min' => 1,
      '#default_value' => $settings['purge_days'],
      '#states' => [
        'invisible' => ['select[name="purge"]' => ['value' => WebformSubmissionStorageInterface::PURGE_NONE]],
        'optional' => ['select[name="purge"]' => ['value' => WebformSubmissionStorageInterface::PURGE_NONE]],
      ],
      '#field_suffix' => $this->t('days'),
    ];

    // Draft settings.
    $form['draft_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Draft settings'),
      '#open' => TRUE,
    ];
    $form['draft_settings']['draft'] = [
      '#type' => 'radios',
      '#title' => $this->t('Allow your users to save and finish the webform later'),
      '#default_value' => $settings['draft'],
      '#options' => [
        WebformInterface::DRAFT_NONE => $this->t('Disabled'),
        WebformInterface::DRAFT_AUTHENTICATED => $this->t('Authenticated users'),
        WebformInterface::DRAFT_ALL => $this->t('Authenticated and anonymous users'),
      ],
    ];
    $form['draft_settings']['draft_message'] = [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t('Please make sure to enable the <a href=":href">automatic purging of draft submissions</a>, to ensure that your database is not filled with abandoned anonymous submissions in draft.', [':href' => Url::fromRoute('<none>', [], ['fragment' => 'edit-purge'])->toString()]),
      '#states' => [
        'visible' => [
          ':input[name="draft"]' => ['value' => WebformInterface::DRAFT_ALL],
          ':input[name="purge"]' => [
            ['value' => WebformSubmissionStorageInterface::PURGE_NONE],
            ['value' => WebformSubmissionStorageInterface::PURGE_COMPLETED],
          ],
        ],
      ],
    ];
    $form['draft_settings']['draft_container'] = [
      '#type' => 'container',
      '#states' => [
        'invisible' => [
          ':input[name="draft"]' => ['value' => WebformInterface::DRAFT_NONE],
        ],
      ],
    ];
    $form['draft_settings']['draft_container']['draft_multiple'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to save multiple drafts'),
      "#description" => $this->t('If checked, users will be able save and resume multiple drafts.'),
      '#return_value' => TRUE,
      '#default_value' => $settings['draft_multiple'],
    ];
    $form['draft_settings']['draft_container']['draft_auto_save'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically save as draft when paging, previewing, and when there are validation errors.'),
      "#description" => $this->t('Automatically save partial submissions when users click the "Preview" button or when validation errors prevent a webform from being submitted.'),
      '#return_value' => TRUE,
      '#default_value' => $settings['draft_auto_save'],
    ];
    $form['draft_settings']['draft_container']['draft_saved_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Draft saved message'),
      '#description' => $this->t('Message to be displayed when a draft is saved.'),
      '#default_value' => $settings['draft_saved_message'],
    ];
    $form['draft_settings']['draft_container']['draft_loaded_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Draft loaded message'),
      '#description' => $this->t('Message to be displayed when a draft is loaded.'),
      '#default_value' => $settings['draft_loaded_message'],
    ];
    $form['draft_settings']['draft_container']['token_tree_link'] = $this->tokenManager->buildTreeLink();

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    /** @var \Drupal\webform\WebformEntityStorageInterface $webform_storage */
    $webform_storage = $this->entityTypeManager->getStorage('webform');

    /** @var \Drupal\webform\WebformSubmissionStorageInterface $webform_submission_storage */
    $webform_submission_storage = $this->entityTypeManager->getStorage('webform_submission');

    $values = $form_state->getValues();

    // Set customize submission user columns.
    $values['submission_user_columns'] = array_values($values['submission_user_columns']);
    if ($values['submission_user_columns'] == $webform_submission_storage->getUserDefaultColumnNames($webform)) {
      $values['submission_user_columns'] = [];
    }

    // Set next serial number.
    $next_serial = (int) $values['next_serial'];
    $max_serial = $webform_storage->getMaxSerial($webform);
    if ($next_serial < $max_serial) {
      drupal_set_message($this->t('The next submission number was increased to @min to make it higher than existing submissions.', ['@min' => $max_serial]));
      $next_serial = $max_serial;
    }
    $webform_storage->setNextSerial($webform, $next_serial);

    // Remove main properties.
    unset(
      $values['next_serial']
    );

    // Set settings.
    $webform->setSettings($values);

    parent::save($form, $form_state);
  }

}
