<?php

namespace Drupal\webform\EntitySettings;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Entity\View;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Utility\WebformDateHelper;
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

    // Display warning and disable the submission form.
    if ($webform->isResultsDisabled()) {
      $this->messenger()->addWarning($this->t('Saving of submissions is disabled, submission settings, submission limits, purging and the saving of drafts is disabled. Submissions must be sent via an email or handled using a <a href=":href">custom webform handler</a>.', [':href' => $webform->toUrl('handlers')->toString()]));
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
      '#title' => $this->t('Submission general settings'),
      '#open' => TRUE,
    ];
    $form['submission_settings']['submission_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Submission label'),
      '#maxlength' => NULL,
      '#default_value' => $settings['submission_label'],
    ];
    $form['submission_settings']['submission_exception_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Submission exception message'),
      '#description' => $this->t('A message to be displayed if submission handling breaks.'),
      '#default_value' => $settings['submission_exception_message'],
    ];
    $form['submission_settings']['submission_locked_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Submission locked message'),
      '#description' => $this->t('A message to be displayed if submission is locked.'),
      '#default_value' => $settings['submission_locked_message'],
    ];
    $form['submission_settings']['previous_submission_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Previous submission message'),
      '#description' => $this->t('A message to be displayed when there is previous submission.'),
      '#default_value' => $settings['previous_submission_message'],
    ];
    $form['submission_settings']['previous_submissions_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Previous submissions message'),
      '#description' => $this->t('A message to be displayed when there are previous submissions.'),
      '#default_value' => $settings['previous_submissions_message'],
    ];
    $form['submission_settings']['next_serial'] = [
      '#type' => 'number',
      '#title' => $this->t('Next submission number'),
      '#description' => $this->t('The value of the next submission number. This is usually 1 when you start and will go up with each webform submission.'),
      '#min' => 1,
      '#default_value' => $webform_storage->getNextSerial($webform),
    ];
    $form['submission_settings']['token_tree_link'] = $this->tokenManager->buildTreeElement();
    $form['submission_settings']['submission_container']['elements'] = [
      '#type' => 'details',
      '#title' => $this->t('Included submission values'),
      '#description' => $this->t('If you wish to include only parts of the submission when viewing as HTML, table, or plain text, select the elements that should be included. Please note, element specific access controls are still applied to displayed elements.'),
      '#open' => $settings['submission_excluded_elements'] ? TRUE : FALSE,
    ];
    $form['submission_settings']['submission_container']['elements']['submission_excluded_elements'] = [
      '#type' => 'webform_excluded_elements',
      '#webform_id' => $this->getEntity()->id(),
      '#default_value' => $settings['submission_excluded_elements'],
    ];
    $form['submission_settings']['submission_container']['elements']['submission_exclude_empty'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude empty elements'),
      '#return_value' => TRUE,
      '#default_value' => $settings['submission_exclude_empty'],
    ];
    $form['submission_settings']['submission_container']['elements']['submission_exclude_empty_checkbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude unselected checkboxes'),
      '#return_value' => TRUE,
      '#default_value' => $settings['submission_exclude_empty_checkbox'],
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
    $form['submission_behaviors']['form_remote_addr'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Track user IP address'),
      '#description' => $this->t("If checked, a user's IP address will be recorded."),
      '#return_value' => TRUE,
      '#default_value' => $settings['form_remote_addr'],
      '#states' => [
        'visible' => [
          ':input[name="form_confidential"]' => ['checked' => FALSE],
        ],
      ],
      '#weight' => -98,
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
      '#weight' => -97,
    ];
    $behavior_elements = [
      // Form specific behaviors.
      'form_previous_submissions' => [
        'title' => $this->t('Show the notification about previous submissions'),
        'form_description' => $this->t('Show the previous submissions notification that appears when users have previously submitted this form.'),
      ],
      'token_view' => [
        'title' => $this->t('Allow users to view a submission using a secure token'),
        'form_description' => $this->t("If checked users will be able to view a submission using the webform submission's URL appended with the submission's (secure) token.") . ' ' .
          $this->t("The 'tokenized' URL to view a submission will be available when viewing a submission's information and can be inserted into an email using the [webform_submission:view-url] token."),
      ],
      'token_update' => [
        'title' => $this->t('Allow users to update a submission using a secure token'),
        'form_description' => $this->t("If checked users will be able to update a submission using the webform's URL appended with the submission's (secure) token.") . ' ' .
          $this->t("The 'tokenized' URL to update a submission will be available when viewing a submission's information and can be inserted into an email using the [webform_submission:update-url] token.") . ' ' .
          $this->t('Only webforms that are open to new submissions can be updated using the secure token.'),
      ],
      // Global behaviors.
      // @see \Drupal\webform\Form\WebformAdminSettingsForm
      'submission_log' => [
        'title' => $this->t('Log submission events'),
        'all_description' => $this->t('All submission event are being logged for all webforms'),
        'form_description' => $this->t('If checked, events will be logged for submissions to this webform.'),
      ],
      'results_customize' => [
        'title' => $this->t('Allow users to customize the submission results table'),
        'all_description' => $this->t('Users can customize the submission results table for all webforms'),
        'form_description' => $this->t('If checked, users can customize the submission results table for this webform.'),
      ],
    ];
    $this->appendBehaviors($form['submission_behaviors'], $behavior_elements, $settings, $default_settings);
    $form['submission_behaviors']['token_update_warning'] = [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t("Submissions accessed using the (secure) token will by-pass all webform submission access rules."),
      '#states' => [
        'visible' => [
          [':input[name="token_view"]' => ['checked' => TRUE]],
          'or',
          [':input[name="token_update"]' => ['checked' => TRUE]],
        ],
      ],
      '#weight' => $form['submission_behaviors']['token_update']['#weight'] + 1,
    ];

    // User settings.
    $form['submission_user_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission user settings'),
      '#open' => TRUE,
    ];
    $form['submission_user_settings']['submission_user_duplicate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to duplicate previous submissions'),
      '#description' => $this->t('If checked, users will be able to duplicate their previous submissions.'),
      '#return_value' => TRUE,
      '#default_value' => $settings['submission_user_duplicate'],
    ];
    $form['submission_user_settings']['submission_columns'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission user columns'),
      '#description' => $this->t('Below columns are displayed to users who can view previous submissions and/or pending drafts.'),
    ];
    // Submission user columns.
    // @see \Drupal\webform\Form\WebformResultsCustomForm::buildForm
    $available_columns = $webform_submission_storage->getColumns($webform);
    // Remove columns that should never be displayed to users.
    $available_columns = array_diff_key($available_columns, array_flip(['uuid', 'in_draft', 'entity', 'sticky', 'locked', 'notes', 'uid']));
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
    $form['submission_user_settings']['submission_columns']['submission_user_columns'] = [
      '#type' => 'webform_tableselect_sort',
      '#header' => [
        'title' => $this->t('Title'),
        'key' => $this->t('Key'),
      ],
      '#options' => $columns_options,
      '#default_value' => $columns_default_value,
    ];

    // Access denied.
    $form['access_denied'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission access denied settings'),
      '#open' => TRUE,
    ];
    $form['access_denied']['submission_access_denied'] = [
      '#type' => 'radios',
      '#title' => $this->t('When a user is denied access to a submission'),
      '#description' => $this->t('Select what happens when a user is denied access to a submission.') .
        '<br/><br/>' .
        $this->t('Go to <a href=":href">form settings</a> to select what happens when a user is denied access to a webform.', [':href' => Url::fromRoute('entity.webform.settings_form', ['webform' => $webform->id()])->toString()]),
      '#options' => [
        WebformInterface::ACCESS_DENIED_DEFAULT => $this->t('Default (Displays the default access denied page)'),
        WebformInterface::ACCESS_DENIED_PAGE => $this->t('Page (Displays message when access is denied to a submission)'),
        WebformInterface::ACCESS_DENIED_LOGIN => $this->t('Login (Redirects to user login form and displays message)'),
      ],
      '#required' => TRUE,
      '#default_value' => $settings['submission_access_denied'],
    ];
    $form['access_denied']['access_denied_container'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="submission_access_denied"]' => ['!value' => WebformInterface::ACCESS_DENIED_DEFAULT],
        ],
      ],
    ];
    $form['access_denied']['access_denied_container']['submission_access_denied_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access denied title'),
      '#description' => $this->t('Page title to be shown on access denied page'),
      '#default_value' => $settings['submission_access_denied_title'],
      '#states' => [
        'visible' => [
          ':input[name="submission_access_denied"]' => ['value' => WebformInterface::ACCESS_DENIED_PAGE],
        ],
      ],
    ];
    $form['access_denied']['access_denied_container']['submission_access_denied_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Access denied message'),
      '#description' => $this->t('Will be displayed either in-line or as a status message depending on the setting above.'),
      '#default_value' => $settings['submission_access_denied_message'],
    ];
    $form['access_denied']['access_denied_container']['token_tree_link'] = $this->tokenManager->buildTreeElement();
    $form['access_denied']['access_denied_container']['access_denied_attributes'] = [
      '#type' => 'details',
      '#title' => $this->t('Access denied message attributes'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="submission_access_denied"]' => ['value' => WebformInterface::ACCESS_DENIED_PAGE],
        ],
      ],
    ];
    $form['access_denied']['access_denied_container']['access_denied_attributes']['submission_access_denied_attributes'] = [
      '#type' => 'webform_element_attributes',
      '#title' => $this->t('Access denied message'),
      '#default_value' => $settings['submission_access_denied_attributes'],
    ];

    // Submission limits.
    $form['submission_limits'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission limit settings'),
      '#open' => TRUE,
    ];
    // Submission limits: Total.
    $form['submission_limits']['total'] = [
      '#type' => 'details',
      '#title' => $this->t('Total submissions'),
    ];
    $form['submission_limits']['total']['total_container'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="limit_total_unique"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['submission_limits']['total']['total_container']['limit_total'] = [
      '#type' => 'number',
      '#title' => $this->t('Total submissions limit'),
      '#min' => 1,
      '#default_value' => $settings['limit_total'],
    ];
    $form['submission_limits']['total']['total_container']['limit_total_interval'] = [
      '#type' => 'select',
      '#options' => WebformDateHelper::getIntervalOptions(),
      '#title' => $this->t('Total submissions limit interval'),
      '#default_value' => $settings['limit_total_interval'],
      '#states' => [
        'visible' => [':input[name="limit_total"]' => ['!value' => '']],
      ],
    ];
    $form['submission_limits']['total']['total_container']['entity_limit_total'] = [
      '#type' => 'number',
      '#title' => $this->t('Total submissions limit per source entity'),
      '#min' => 1,
      '#default_value' => $settings['entity_limit_total'],
    ];
    $form['submission_limits']['total']['total_container']['entity_limit_total_interval'] = [
      '#type' => 'select',
      '#options' => WebformDateHelper::getIntervalOptions(),
      '#title' => $this->t('Total submissions limit interval per source entity'),
      '#default_value' => $settings['entity_limit_total_interval'],
      '#states' => [
        'visible' => [':input[name="entity_limit_total"]' => ['!value' => '']],
      ],
    ];
    $form['submission_limits']['total']['total_container']['limit_total_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Total submissions limit message'),
      '#min' => 1,
      '#default_value' => $settings['limit_total_message'],
      '#states' => [
        'visible' => [
          [':input[name="limit_total"]' => ['!value' => '']],
          'or',
          [':input[name="entity_limit_total"]' => ['!value' => '']],
        ],
      ],
    ];
    $form['submission_limits']['total']['total_container']['token_tree_link'] = $this->tokenManager->buildTreeElement();
    if ($form['submission_limits']['total']['total_container']['token_tree_link']) {
      $form['submission_limits']['total']['total_container']['token_tree_link'] += [
        '#states' => [
          'visible' => [
            [':input[name="limit_total"]' => ['!value' => '']],
            'or',
            [':input[name="entity_limit_total"]' => ['!value' => '']],
          ],
        ],
      ];
    }
    $form['submission_limits']['total']['limit_total_unique'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Limit total to one submission per webform/source entity'),
      '#default_value' => $settings['limit_total_unique'],
    ];
    $form['submission_limits']['total']['limit_total_unique_info'] = [
      '#type' => 'webform_message',
      '#message_message' => $this->t('Only submission administrators will only be able to create and update the unique submission.') . '<br/><br/>' .
        $this->t('Webform blocks can be used to place this webform on the desired source entity types.'),
      '#message_type' => 'info',
      '#message_close' => TRUE,
      '#message_storage' => WebformMessage::STORAGE_SESSION,
      '#states' => [
        'visible' => [
          ':input[name="limit_total_unique"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['submission_limits']['total']['limit_total_unique_warning'] = [
      '#type' => 'webform_message',
      '#message_message' => $this->t("Please make sure users are allowed to 'view any submission' and 'edit any submission'."),
      '#message_type' => 'warning',
      '#message_close' => TRUE,
      '#message_storage' => WebformMessage::STORAGE_SESSION,
      '#states' => [
        'visible' => [
          ':input[name="limit_total_unique"]' => ['checked' => TRUE],
        ],
      ],
    ];
    // Submission limits: User.
    $form['submission_limits']['user'] = [
      '#type' => 'details',
      '#title' => $this->t('Per user'),
      '#description' => $this->t('Limit the number of submissions per user. A user is identified by their user id if logged-in, or by their Cookie if anonymous.'),
      '#states' => [
        'visible' => [
          ':input[name="limit_total_unique"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['submission_limits']['user']['user_container'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="limit_user_unique"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['submission_limits']['user']['user_container']['limit_user'] = [
      '#type' => 'number',
      '#title' => $this->t('Per user submission limit'),
      '#min' => 1,
      '#default_value' => $settings['limit_user'],
    ];
    $form['submission_limits']['user']['user_container']['limit_user_interval'] = [
      '#type' => 'select',
      '#options' => WebformDateHelper::getIntervalOptions(),
      '#title' => $this->t('Per user submission limit interval'),
      '#default_value' => $settings['limit_user_interval'],
      '#states' => [
        'visible' => [':input[name="limit_user"]' => ['!value' => '']],
      ],
    ];
    $form['submission_limits']['user']['user_container']['entity_limit_user'] = [
      '#type' => 'number',
      '#min' => 1,
      '#title' => $this->t('Per user submission limit per source entity'),
      '#default_value' => $settings['entity_limit_user'],
    ];
    $form['submission_limits']['user']['user_container']['entity_limit_user_interval'] = [
      '#type' => 'select',
      '#options' => WebformDateHelper::getIntervalOptions(),
      '#title' => $this->t('Per user submission limit interval per source entity'),
      '#default_value' => $settings['entity_limit_user_interval'],
      '#states' => [
        'visible' => [':input[name="entity_limit_user"]' => ['!value' => '']],
      ],
    ];
    $form['submission_limits']['user']['user_container']['limit_user_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Per user submission limit message'),
      '#default_value' => $settings['limit_user_message'],
      '#states' => [
        'visible' => [
          [':input[name="limit_user"]' => ['!value' => '']],
          'or',
          [':input[name="entity_limit_user"]' => ['!value' => '']],
        ],
      ],
    ];
    $form['submission_limits']['user']['user_container']['token_tree_link'] = $this->tokenManager->buildTreeElement();
    if ($form['submission_limits']['user']['user_container']['token_tree_link']) {
      $form['submission_limits']['user']['user_container']['token_tree_link'] += [
        '#states' => [
          'visible' => [
            [':input[name="limit_user"]' => ['!value' => '']],
            'or',
            [':input[name="entity_limit_user"]' => ['!value' => '']],
          ],
        ],
      ];
    }
    $form['submission_limits']['user']['limit_user_unique'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Limit users to one submission per webform/source entity'),
      '#default_value' => $settings['limit_user_unique'],
    ];
    $form['submission_limits']['user']['limit_user_unique_info'] = [
      '#type' => 'webform_message',
      '#message_message' => $this->t('Only authenticated users will be able to create and update their unique submission.'),
      '#message_type' => 'info',
      '#message_close' => TRUE,
      '#message_storage' => WebformMessage::STORAGE_SESSION,
      '#states' => [
        'visible' => [
          ':input[name="limit_user_unique"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['submission_limits']['user']['limit_user_unique_warning'] = [
      '#type' => 'webform_message',
      '#message_message' => $this->t("Please make sure authenticated users are allowed to 'view own submission' and 'edit own submission'."),
      '#message_type' => 'warning',
      '#message_close' => TRUE,
      '#message_storage' => WebformMessage::STORAGE_SESSION,
      '#states' => [
        'visible' => [
          ':input[name="limit_user_unique"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Purge settings.
    $form['purge_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission purge settings'),
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
      '#title' => $this->t('Submission draft settings'),
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
    $form['draft_settings']['draft_container']['draft_pending_single_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Draft pending single draft message'),
      '#description' => $this->t('Message to be displayed when a single draft is saved.'),
      '#default_value' => $settings['draft_pending_single_message'],
      '#states' => [
        'visible' => [
          ':input[name="draft_multiple"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['draft_settings']['draft_container']['draft_pending_multiple_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Draft pending multiple drafts message'),
      '#description' => $this->t('Message to be displayed when multiple drafts are saved.'),
      '#default_value' => $settings['draft_pending_multiple_message'],
      '#states' => [
        'visible' => [
          ':input[name="draft_multiple"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['draft_settings']['draft_container']['draft_auto_save'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically save as draft when paging, previewing, and when there are validation errors'),
      "#description" => $this->t('Automatically save partial submissions when users click the "Next Page", "Previous Page", or "Preview" buttons or when validation errors prevent a webform from being submitted.'),
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
    $form['draft_settings']['draft_container']['token_tree_link'] = $this->tokenManager->buildTreeElement();

    // Autofill settings.
    $form['autofill_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission autofill settings'),
      '#open' => TRUE,
    ];
    $form['autofill_settings']['autofill'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autofill with previous submission data'),
      '#return_value' => TRUE,
      '#default_value' => $settings['autofill'],
    ];
    $form['autofill_settings']['autofill_container'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="autofill"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['autofill_settings']['autofill_container']['autofill_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Autofill message'),
      '#description' => $this->t('A message to be displayed when form is autofilled with previous submission data.'),
      '#default_value' => $settings['autofill_message'],
    ];
    $form['autofill_settings']['autofill_container']['elements'] = [
      '#type' => 'details',
      '#title' => $this->t('Autofill elements'),
      '#open' => $settings['autofill_excluded_elements'] ? TRUE : FALSE,
    ];
    $form['autofill_settings']['autofill_container']['elements']['autofill_excluded_elements'] = [
      '#type' => 'webform_excluded_elements',
      '#webform_id' => $this->getEntity()->id(),
      '#default_value' => $settings['autofill_excluded_elements'],
    ];
    $form['autofill_settings']['autofill_container']['token_tree_link'] = $this->tokenManager->buildTreeElement();

    // Submission views.
    $form['views_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission views settings'),
      '#open' => TRUE,
    ];
    if (!$this->moduleHandler->moduleExists('webform_views')) {
      $form['views_settings']['message'] = [
        '#type' => 'webform_message',
        '#message_type' => 'info',
        '#message_message' => $this->t('To expose your webform elements to your webform submission views. Please install the <a href=":href">Webform Views Integration</a> module.', [':href' => 'https://www.drupal.org/project/webform_views']),
        '#message_close' => TRUE,
        '#message_storage' => WebformMessage::STORAGE_SESSION,
      ];
    }
    if ($this->moduleHandler->moduleExists('views_ui')
      && $this->currentUser()->hasPermission('administer views')
      && ($view = View::load('webform_submissions'))
      && ($view->access('duplicate'))) {

      $form['views_settings']['submission_views_create'] = [
        '#type' => 'link',
        '#title' => $this->t('Create new submission view'),
        '#url' => Url::fromRoute(
          'entity.view.duplicate_form',
          ['view' => 'webform_submissions']
        ),
        '#attributes' => [
          'target' => '_blank',
          'class' => ['button', 'button-action', 'button--small'],
        ],
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];
    }
    $form['views_settings']['submission_views'] = [
      '#type' => 'webform_submission_views',
      '#title' => $this->t('Submission views'),
      '#title_display' => 'invisible',
      '#default_value' => $settings['submission_views'],
    ];
    $form['views_settings']['submission_views_replace'] = [
      '#type' => 'webform_submission_views_replace',
      '#default_value' => $settings['submission_views_replace'],
    ];

    $this->tokenManager->elementValidate($form);

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
      $this->messenger()->addStatus($this->t('The next submission number was increased to @min to make it higher than existing submissions.', ['@min' => $max_serial]));
      $next_serial = $max_serial;
    }
    $webform_storage->setNextSerial($webform, $next_serial);

    // Limit total unique.
    if (!empty($values['limit_total_unique'])) {
      $values['limit_total'] = NULL;
      $values['limit_total_interval'] = NULL;
      $values['limit_total_message'] = '';
      $values['entity_limit_total'] = NULL;
      $values['entity_limit_total_interval'] = NULL;
      $values['limit_user'] = NULL;
      $values['limit_user_interval'] = NULL;
      $values['limit_user_message'] = '';
      $values['entity_limit_user'] = NULL;
      $values['entity_limit_user_interval'] = NULL;
    }

    // Limit user unique.
    if (!empty($values['limit_user_unique'])) {
      $values['limit_user'] = NULL;
      $values['limit_user_interval'] = NULL;
      $values['limit_user_message'] = '';
      $values['entity_limit_user'] = NULL;
      $values['entity_limit_user_interval'] = NULL;
    }

    // Remove main properties.
    unset(
      $values['next_serial']
    );

    // Set settings.
    $webform->setSettings($values);

    parent::save($form, $form_state);
  }

}
