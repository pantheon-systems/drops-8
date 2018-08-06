<?php

namespace Drupal\webform\EntitySettings;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\webform\Plugin\WebformHandlerInterface;
use Drupal\webform\WebformMessageManagerInterface;
use Drupal\webform\WebformThirdPartySettingsManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform general settings.
 */
class WebformEntitySettingsGeneralForm extends WebformEntitySettingsBaseForm {

  /**
   * The webform message manager.
   *
   * @var \Drupal\webform\WebformMessageManagerInterface
   */
  protected $messageManager;

  /**
   * The webform third party settings manager.
   *
   * @var \Drupal\webform\WebformThirdPartySettingsManagerInterface
   */
  protected $thirdPartySettingsManager;

  /**
   * Constructs a WebformEntitySettingsGeneralForm.
   *
   * @param \Drupal\webform\WebformMessageManagerInterface $message_manager
   *   The webform message manager.
   * @param \Drupal\webform\WebformThirdPartySettingsManagerInterface $third_party_settings_manager
   *   The webform third party settings manager.
   */
  public function __construct(WebformMessageManagerInterface $message_manager, WebformThirdPartySettingsManagerInterface $third_party_settings_manager) {
    $this->messageManager = $message_manager;
    $this->thirdPartySettingsManager = $third_party_settings_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webform.message_manager'),
      $container->get('webform.third_party_settings_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->entity;

    // Set message manager's webform.
    $this->messageManager->setWebform($webform);

    $default_settings = $this->config('webform.settings')->get('settings');
    $settings = $webform->getSettings();

    // General settings.
    $form['general_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => TRUE,
    ];
    $form['general_settings']['id'] = [
      '#type' => 'item',
      '#title' => $this->t('ID'),
      '#markup' => $webform->id(),
      '#value' => $webform->id(),
    ];
    $form['general_settings']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 255,
      '#default_value' => $webform->label(),
      '#required' => TRUE,
      '#id' => 'title',
    ];
    $form['general_settings']['description'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Administrative description'),
      '#default_value' => $webform->get('description'),
    ];
    /** @var \Drupal\webform\WebformEntityStorageInterface $webform_storage */
    $webform_storage = $this->entityTypeManager->getStorage('webform');
    $form['general_settings']['category'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Category'),
      '#options' => $webform_storage->getCategories(),
      '#empty_option' => '<' . $this->t('None') . '>',
      '#default_value' => $webform->get('category'),
    ];
    $form['general_settings']['template'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow this webform to be used as a template'),
      '#description' => $this->t('If checked, this webform will be available as a template to all users who can create new webforms.'),
      '#return_value' => TRUE,
      '#access' => $this->moduleHandler->moduleExists('webform_templates'),
      '#default_value' => $webform->isTemplate(),
    ];
    $form['general_settings']['results_disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable saving of submissions'),
      '#description' => $this->t('If saving of submissions is disabled, submission settings, submission limits, purging and the saving of drafts will be disabled. Submissions must be sent via an email or processed using a custom <a href=":href">webform handler</a>.', [':href' => Url::fromRoute('entity.webform.handlers', ['webform' => $webform->id()])->toString()]),
      '#return_value' => TRUE,
      '#default_value' => $settings['results_disabled'],
    ];

    // Display warning when submission handler requires submissions to be saved
    // to the database.
    $is_submission_required = $webform->getHandlers(NULL, TRUE, NULL, WebformHandlerInterface::SUBMISSION_REQUIRED)->count();
    if ($is_submission_required) {
      $form['general_settings']['results_disabled']['#default_value'] = FALSE;
      $form['general_settings']['results_disabled']['#disabled'] = TRUE;
      unset($form['general_settings']['results_disabled']['#description']);
      $form['general_settings']['results_disabled_required'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => $this->messageManager->get(WebformMessageManagerInterface::HANDLER_SUBMISSION_REQUIRED),
      ];
    }

    // Display warning when disabling the saving of submissions with no
    // handlers.
    $is_results_processed = $webform->getHandlers(NULL, TRUE, WebformHandlerInterface::RESULTS_PROCESSED)->count();
    if (!$is_results_processed) {
      $form['general_settings']['results_disabled_error'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => $this->messageManager->get(WebformMessageManagerInterface::FORM_SAVE_EXCEPTION),
        '#states' => [
          'visible' => [
            ':input[name="results_disabled"]' => ['checked' => TRUE],
            ':input[name="results_disabled_ignore"]' => ['checked' => FALSE],
          ],
        ],
      ];
      $form['general_settings']['results_disabled_ignore'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Ignore disabled results warning'),
        '#description' => $this->t("If checked, all warnings and log messages about 'This webform is currently not saving any submitted data.' will be suppressed."),
        '#return_value' => TRUE,
        '#default_value' => $settings['results_disabled_ignore'],
        '#states' => [
          'visible' => [
            ':input[name="results_disabled"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    // Page settings.
    $form['page_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('URL path settings'),
      '#open' => TRUE,
    ];
    $default_page_submit_path = trim($default_settings['default_page_base_path'], '/') . '/' . str_replace('_', '-', $webform->id());
    $t_args = [
      ':node_href' => ($this->moduleHandler->moduleExists('node')) ? Url::fromRoute('node.add', ['node_type' => 'webform'])->toString() : '',
      ':block_href' => ($this->moduleHandler->moduleExists('block')) ? Url::fromRoute('block.admin_display')->toString() : '',
      ':view_href' => $webform->toUrl()->toString(),
      ':test_href' => $webform->toUrl('test-form')->toString(),
    ];
    $default_settings['default_page_submit_path'] = $default_page_submit_path;
    $default_settings['default_page_confirm_path'] = $default_page_submit_path . '/confirmation';
    $form['page_settings']['page'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to post submissions from a dedicated URL'),
      '#description' => $this->t('If unchecked, this webform must be attached to a <a href=":node_href">node</a> or a <a href=":block_href">block</a> to receive submissions.', $t_args),
      '#return_value' => TRUE,
      '#default_value' => $settings['page'],
    ];
    $form['page_settings']['page_message'] = [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t('Any user who can update this webform will still be able to <a href=":view_href">view</a> and <a href=":test_href">test</a> this webform with the administrative theme.', $t_args),
      '#states' => [
        'visible' => [
          ':input[name="page"]' => ['checked' => FALSE],
        ],
      ],
    ];
    if ($this->moduleHandler->moduleExists('path')) {
      $form['page_settings']['page_submit_path'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Webform URL alias'),
        '#description' => $this->t('Optionally specify an alternative URL by which the webform submit page can be accessed.', $t_args),
        '#default_value' => $settings['page_submit_path'],
        '#states' => [
          'visible' => [
            ':input[name="page"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $form['page_settings']['page_confirm_path'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Confirmation page URL alias'),
        '#description' => $this->t('Optionally specify an alternative URL by which the webform confirmation page can be accessed.', $t_args),
        '#default_value' => $settings['page_confirm_path'],
        '#states' => [
          'visible' => [
            ':input[name="page"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    // Ajax settings.
    $elements = $webform->getElementsDecoded();

    $form['ajax_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Ajax settings'),
      '#open' => TRUE,
      '#access' => empty($elements['#method']),
      '#states' => [
        'visible' => [
          ':input[name="method"]' => ['value' => ''],
        ],
      ],
    ];
    $form['ajax_settings']['ajax'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Ajax'),
      '#description' => $this->t('If checked, paging, saving of drafts, previews, submissions, and confirmations will not initiate a page refresh.'),
      '#return_value' => TRUE,
      '#default_value' => $settings['ajax'],
    ];
    $form['ajax_settings']['ajax_scroll_top'] = [
      '#type' => 'radios',
      '#title' => $this->t('On Ajax load, scroll to the top of the...'),
      '#description' => $this->t("Select where the page should be scrolled to when paging, saving of drafts, previews, submissions, and confirmations. Select 'None' to disable scrolling."),
      '#options' => [
        '' => $this->t('None'),
        'form' => $this->t('Form'),
        'page' => $this->t('Page'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="ajax"]' => ['checked' => TRUE],
        ],
      ],
      '#default_value' => $settings['ajax_scroll_top'],
    ];

    // Author information.
    $form['author_information'] = [
      '#type' => 'details',
      '#title' => $this->t('Author information'),
      '#access' => $this->currentUser()->hasPermission('administer webform'),
    ];
    $form['author_information']['uid'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Authored by'),
      '#description' => $this->t("The username of the webform author/owner."),
      '#target_type' => 'user',
      '#settings' => [
        'match_operator' => 'CONTAINS',
      ],
      '#selection_settings' => [
        'include_anonymous' => TRUE,
      ],
      '#default_value' => $webform->getOwner(),
    ];

    // Third party settings.
    $form['third_party_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Third party settings'),
      '#description' => $this->t('Third party settings allow contrib and custom modules to define webform specific customization settings.'),
      '#tree' => TRUE,
    ];
    $this->thirdPartySettingsManager->alter('webform_third_party_settings_form', $form, $form_state);
    if (!Element::children($form['third_party_settings'])) {
      $form['third_party_settings']['#access'] = FALSE;
    }
    else {
      ksort($form['third_party_settings']);
    }

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    // Set third party settings.
    if (isset($values['third_party_settings'])) {
      $third_party_settings = $values['third_party_settings'];
      foreach ($third_party_settings as $module => $third_party_setting) {
        foreach ($third_party_setting as $key => $value) {
          $webform->setThirdPartySetting($module, $key, $value);
        }
      }
      // Remove third party settings.
      unset($values['third_party_settings']);
    }

    // Remove main properties.
    unset(
      $values['id'],
      $values['title'],
      $values['description'],
      $values['category'],
      $values['template'],
      $values['uid']
    );

    // Set settings.
    $webform->setSettings($values);

    parent::save($form, $form_state);
  }

}
