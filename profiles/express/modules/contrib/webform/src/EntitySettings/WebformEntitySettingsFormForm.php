<?php

namespace Drupal\webform\EntitySettings;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\Utility\WebformDateHelper;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform form settings.
 */
class WebformEntitySettingsFormForm extends WebformEntitySettingsBaseForm {

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * Constructs a WebformEntitySettingsFormForm.
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
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->entity;

    $default_settings = $this->config('webform.settings')->get('settings');
    $settings = $webform->getSettings();

    // Form settings.
    $form['form_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Form general settings'),
      '#open' => TRUE,
    ];
    $form['form_settings']['status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Form status'),
      '#description' => $this->t('Form status applies to all instances of this webform. For example, if this webform is closed, all webform nodes and blocks will be closed.'),
      '#default_value' => $webform->get('status'),
      '#options' => [
        WebformInterface::STATUS_OPEN => $this->t('Open'),
        WebformInterface::STATUS_CLOSED => $this->t('Closed'),
        WebformInterface::STATUS_SCHEDULED => $this->t('Scheduled'),
      ],
      '#options_display' => 'side_by_side',
    ];

    // @see \Drupal\webform\Plugin\Field\FieldWidget\WebformEntityReferenceAutocompleteWidget::formElement
    $form['form_settings']['scheduled'] = [
      '#type' => 'item',
      '#input' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="status"]' => ['value' => WebformInterface::STATUS_SCHEDULED],
        ],
      ],
    ];
    $t_args = [
      ':page_cache_href' => 'https://www.drupal.org/docs/8/administering-a-drupal-8-site/internal-page-cache',
      ':issue_href' => 'https://www.drupal.org/node/2352009',
      ':cache_control_override_href' => 'https://www.drupal.org/project/cache_control_override',
    ];
    if ($this->moduleHandler->moduleExists('page_cache') && !$this->moduleHandler->moduleExists('cache_control_override')) {
      $form['form_settings']['scheduled']['page_cache'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_close' => TRUE,
        '#message_storage' => WebformMessage::STORAGE_SESSION,
        '#message_message' => $this->t('Scheduled forms do not work as expected for anonymous users when Drupal\'s <a href=":page_cache_href">Internal Page Cache</a> module is enabled. This is a <a href=":issue_href">known issue</a>.', $t_args) . '<br/><br/>' .
          '<strong>' . $this->t('It is strongly recommended that you install the <a href=":cache_control_override_href">Cache Control Override</a> module.', $t_args) . '</strong>',
      ];
    }
    $form['form_settings']['scheduled']['open'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Open'),
      '#prefix' => '<div class="container-inline form-item">',
      '#suffix' => '</div>',
      '#default_value' => $webform->get('open') ? DrupalDateTime::createFromTimestamp(strtotime($webform->get('open'))) : NULL,
      '#help' => FALSE,
      '#description' => [
        '#type' => 'webform_help',
        '#help' => $this->t('If the open date/time is left blank, this form will immediately be opened.'),
        '#help_title' => $this->t('Open'),
      ],
    ];
    $form['form_settings']['scheduled']['close'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Close'),
      '#title_display' => 'inline',
      '#prefix' => '<div class="container-inline form-item">',
      '#suffix' => '</div>',
      '#help' => FALSE,
      '#description' => [
        '#type' => 'webform_help',
        '#help' => $this->t('If the close date/time is left blank, this webform will never be closed.'),
        '#help_title' => $this->t('Close'),
      ],
      '#default_value' => $webform->get('close') ? DrupalDateTime::createFromTimestamp(strtotime($webform->get('close'))) : NULL,
    ];
    // If the Webform templates module is enabled and webform is template, hide status and scheduled.
    if ($this->moduleHandler->moduleExists('webform_templates') && $webform->isTemplate()) {
      $form['form_settings']['status']['#access'] = FALSE;
      $form['form_settings']['scheduled']['#access'] = FALSE;
    }
    $form['form_settings']['form_title'] = [
      '#type' => 'select',
      '#title' => $this->t('Form title display'),
      '#description' => $this->t("Select how the form's title is displayed when this webform is attached to a source entity. This title is only displayed when a webform is linked to from a source entity or opened in dialog."),
      '#options' => [
        WebformInterface::TITLE_SOURCE_ENTITY_WEBFORM => $this->t('Source entity: Webform'),
        WebformInterface::TITLE_WEBFORM_SOURCE_ENTITY => $this->t('Webform: Source entity'),
        WebformInterface::TITLE_WEBFORM => $this->t('Webform'),
        WebformInterface::TITLE_SOURCE_ENTITY => $this->t('Source entity'),
      ],
      '#required' => TRUE,
      '#default_value' => $settings['form_title'],
    ];
    $form['form_settings']['form_open_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Form open message'),
      '#description' => $this->t('A message to be displayed notifying the user that the webform is going to be opening to submissions. The opening message will only be displayed when a webform is scheduled to be opened.'),
      '#default_value' => $settings['form_open_message'],
    ];
    $form['form_settings']['form_close_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Form closed message'),
      '#description' => $this->t("A message to be displayed notifying the user that the webform is closed. The closed message will be displayed when a webform's status is closed or a submission limit is reached."),
      '#default_value' => $settings['form_close_message'],
    ];
    $form['form_settings']['form_exception_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Form exception message'),
      '#description' => $this->t('A message to be displayed if the webform breaks.'),
      '#default_value' => $settings['form_exception_message'],
    ];
    $form['form_settings']['token_tree_link'] = $this->tokenManager->buildTreeElement();
    $form['form_settings']['form_attributes'] = [
      '#type' => 'details',
      '#title' => $this->t('Form attributes'),
      '#open' => TRUE,
    ];
    $elements = $webform->getElementsDecoded();
    $form['form_settings']['form_attributes']['attributes'] = [
      '#type' => 'webform_element_attributes',
      '#title' => $this->t('Form'),
      '#classes' => $this->config('webform.settings')->get('settings.form_classes'),
      '#default_value' => (isset($elements['#attributes'])) ? $elements['#attributes'] : [],
    ];

    // Form behaviors.
    $form['form_behaviors'] = [
      '#type' => 'details',
      '#title' => $this->t('Form behaviors'),
      '#open' => TRUE,
    ];
    $form_behaviors = $this->getFormBehaviors();
    $this->appendBehaviors($form['form_behaviors'], $form_behaviors, $settings, $default_settings);
    $form['form_behaviors']['form_prepopulate_source_entity_required']['#states'] = [
      'visible' => [':input[name="form_prepopulate_source_entity"]' => ['checked' => TRUE]],
    ];
    // Source entity type.
    $entity_type_options = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      $entity_type_options[$entity_type_id] = $entity_type->getLabel();
    }
    uasort($entity_type_options, 'strnatcasecmp');
    $form['form_behaviors']['form_prepopulate_source_entity_type'] = [
      '#type' => 'select',
      '#title' => 'Type of source entity to be populated using query string parameters',
      '#weight' => ++$form['form_behaviors']['form_prepopulate_source_entity_required']['#weight'],
      '#empty_option' => $this->t('- None -'),
      '#options' => $entity_type_options,
      '#default_value' => $settings['form_prepopulate_source_entity_type'],
      '#states' => [
        'visible' => [':input[name="form_prepopulate_source_entity"]' => ['checked' => TRUE]],
      ],
    ];
    // Hide "Submit previous page when browser back button is clicked" when
    // Ajax is enabled.
    if ($settings['ajax']) {
      $form['form_behaviors']['form_submit_back']['#default'] = TRUE;
      $form['form_behaviors']['form_submit_back']['#disabled'] = TRUE;
      $form['form_behaviors']['form_submit_back']['#description'] .= '<br/><br/><em>' . t('This behavior is not supoported when Ajax is enabled.') . '</em>';
    }
    // Disable warning about drafts.
    if ($settings['draft'] !== WebformInterface::DRAFT_NONE) {
      $form['form_behaviors']['form_reset_message'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => $this->t('Currently loaded drafts will be deleted when the form is reset.'),
        '#weight' => $form['form_behaviors']['form_reset']['#weight'] + 1,
        '#states' => [
          'visible' => [
            ':input[name="form_reset"]' => ['checked' => TRUE],
          ],
        ],

      ];
    }

    // Access denied.
    $form['access_denied'] = [
      '#type' => 'details',
      '#title' => $this->t('Form access denied settings'),
      '#open' => TRUE,
    ];
    $form['access_denied']['form_access_denied'] = [
      '#type' => 'radios',
      '#title' => $this->t('When a user is denied access to this webform'),
      '#description' => $this->t('Select what happens when a user is denied access to this webform.') .
        '<br/><br/>' .
        $this->t('Go to <a href=":href">submission settings</a> to select what happens when a user is denied access to submissions.', [':href' => Url::fromRoute('entity.webform.settings_submissions', ['webform' => $webform->id()])->toString()]),

      '#options' => [
        WebformInterface::ACCESS_DENIED_DEFAULT => $this->t('Default (Displays the default access denied page)'),
        WebformInterface::ACCESS_DENIED_MESSAGE => $this->t('Inline (Displays message when access is denied to field, nodes, and blocks)'),
        WebformInterface::ACCESS_DENIED_PAGE => $this->t('Page (Displays message when access is denied to forms, fields, nodes, and blocks)'),
        WebformInterface::ACCESS_DENIED_LOGIN => $this->t('Login (Redirects to user login form and displays message. Field, nodes, and block only display the message.)'),
      ],
      '#required' => TRUE,
      '#default_value' => $settings['form_access_denied'],
    ];
    $form['access_denied']['access_denied_container'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="form_access_denied"]' => ['!value' => WebformInterface::ACCESS_DENIED_DEFAULT],
        ],
      ],
    ];
    $form['access_denied']['access_denied_container']['form_access_denied_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access denied title'),
      '#description' => $this->t('Page title to be shown on access denied page'),
      '#default_value' => $settings['form_access_denied_title'],
      '#states' => [
        'visible' => [
          ':input[name="form_access_denied"]' => ['value' => WebformInterface::ACCESS_DENIED_PAGE],
        ],
      ],
    ];
    $form['access_denied']['access_denied_container']['form_access_denied_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Access denied message'),
      '#description' => $this->t('Will be displayed either in-line or as a status message depending on the setting above.'),
      '#default_value' => $settings['form_access_denied_message'],
    ];
    $form['access_denied']['access_denied_container']['token_tree_link'] = $this->tokenManager->buildTreeElement();
    $form['access_denied']['access_denied_container']['access_denied_attributes'] = [
      '#type' => 'details',
      '#title' => $this->t('Access denied message attributes'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          [':input[name="form_access_denied"]' => ['value' => WebformInterface::ACCESS_DENIED_MESSAGE]],
          'or',
          [':input[name="form_access_denied"]' => ['value' => WebformInterface::ACCESS_DENIED_PAGE]],
        ],
      ],
    ];
    $form['access_denied']['access_denied_container']['access_denied_attributes']['form_access_denied_attributes'] = [
      '#type' => 'webform_element_attributes',
      '#title' => $this->t('Access denied message'),
      '#default_value' => $settings['form_access_denied_attributes'],
    ];

    // Wizard settings.
    $form['wizard_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Form wizard settings'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="method"]' => ['value' => ''],
        ],
      ],
    ];
    $form['wizard_settings']['wizard_progress_bar'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show wizard progress bar'),
      '#description' => $this->t('If checked, a progress bar will displayed about the form.'),
      '#return_value' => TRUE,
      '#default_value' => $settings['wizard_progress_bar'],
    ];
    $form['wizard_settings']['wizard_progress_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link to previous pages in progress bar'),
      '#description' => $this->t('If checked, previous pages will be link in the progress bar.'),
      '#return_value' => TRUE,
      '#default_value' => $settings['wizard_progress_link'],
      '#states' => [
        'visible' => [
          ':input[name="wizard_progress_bar"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['wizard_settings']['wizard_progress_pages'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show wizard progress pages'),
      '#description' => $this->t('If checked, the current page and total remaining pages will be displayed. (i.e. Page 1 of 10)'),
      '#return_value' => TRUE,
      '#default_value' => $settings['wizard_progress_pages'],
    ];
    $form['wizard_settings']['wizard_progress_percentage'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show wizard progress percentage'),
      '#description' => $this->t('If checked, the percentage of completed pages will be displayed. (i.e. 10%)'),
      '#return_value' => TRUE,
      '#default_value' => $settings['wizard_progress_percentage'],
    ];
    $form['wizard_settings']['wizard_preview_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link to previous pages in preview'),
      '#description' => $this->t("If checked, the preview page will included 'Edit' buttons for each previous page.") . '<br/><br/>' .
        '<em>' . $this->t("This settings is only available when 'Enable preview page' is enabled.") . '</em>',
      '#return_value' => TRUE,
      '#default_value' => $settings['wizard_preview_link'],
      '#states' => [
        'enabled' => [
          ':input[name="preview"]' => ['!value' => DRUPAL_DISABLED],
        ],
      ],
    ];
    $form['wizard_settings']['wizard_progress_states'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Update wizard progress bar's pages based on conditions"),
      '#description' => $this->t("If checked, the wizard's progress bar's pages will be hidden on shown based on each pages conditional logic."),
      '#return_value' => TRUE,
      '#default_value' => $settings['wizard_progress_states'],
    ];
    $form['wizard_settings']['wizard_confirmation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include confirmation page in progress'),
      '#description' => $this->t("If checked, the confirmation page will be included in the progress bar."),
      '#return_value' => TRUE,
      '#default_value' => $settings['wizard_confirmation'],
      '#states' => [
        'visible' => [
          [':input[name="wizard_progress_bar"]' => ['checked' => TRUE]],
          'or',
          [':input[name="wizard_progress_pages"]' => ['checked' => TRUE]],
          'or',
          [':input[name="wizard_progress_percentage"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    $form['wizard_settings']['wizard_start_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wizard start label'),
      '#description' => $this->t('The first page label in the progress bar. Subsequent pages are titled by their wizard page title.'),
      '#size' => 20,
      '#default_value' => $settings['wizard_start_label'],
    ];
    $form['wizard_settings']['wizard_confirmation_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wizard end label'),
      '#description' => $this->t("The confirmation page label's in the progress bar."),
      '#size' => 20,
      '#default_value' => $settings['wizard_confirmation_label'],
      '#states' => [
        'visible' => [
          ':input[name="webform_confirmation"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['wizard_settings']['wizard_track'] = [
      '#type' => 'select',
      '#title' => $this->t('Track wizard progress in the URL by'),
      '#description' => $this->t("Progress tracking allows analytic software to capture a multi-step form's progress."),
      '#options' => [
        'name' => $this->t("Page name (?page=contact)"),
        'index' => $this->t("Page index (?page=2)"),
      ],
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $settings['wizard_track'],
    ];

    // Preview settings.
    $form['preview_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Form preview settings'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="method"]' => ['value' => ''],
        ],
      ],
    ];
    $form['preview_settings']['preview'] = [
      '#type' => 'radios',
      '#title' => $this->t('Enable preview page'),
      '#options' => [
        DRUPAL_DISABLED => $this->t('Disabled'),
        DRUPAL_OPTIONAL => $this->t('Optional'),
        DRUPAL_REQUIRED => $this->t('Required'),
      ],
      '#options_display' => 'side_by_side',
      '#description' => $this->t('Add a page for previewing the webform before submitting.'),
      '#default_value' => $settings['preview'],
    ];
    $form['preview_settings']['preview_container'] = [
      '#type' => 'container',
      '#states' => [
        'invisible' => [
          ':input[name="preview"]' => ['value' => DRUPAL_DISABLED],
        ],
      ],
    ];
    $form['preview_settings']['preview_container']['preview_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Preview label'),
      '#description' => $this->t("The text displayed within a multi-step wizard's progress bar"),
      '#default_value' => $settings['preview_label'],
    ];
    $form['preview_settings']['preview_container']['preview_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Preview page title'),
      '#description' => $this->t('The title displayed on the preview page.'),
      '#default_value' => $settings['preview_title'],
    ];
    $form['preview_settings']['preview_container']['preview_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Preview message'),
      '#description' => $this->t('A message to be displayed on the preview page.'),
      '#default_value' => $settings['preview_message'],
    ];
    // Elements.
    $form['preview_settings']['preview_container']['elements'] = [
      '#type' => 'details',
      '#title' => $this->t('Included preview values'),
      '#description' => $this->t('If you wish to include only parts of the submission in the preview, select the elements that should be included. Please note, element specific access controls are still applied to displayed elements.'),
      '#open' => $settings['preview_excluded_elements'] ? TRUE : FALSE,
    ];
    $form['preview_settings']['preview_container']['elements']['preview_excluded_elements'] = [
      '#type' => 'webform_excluded_elements',
      '#webform_id' => $this->getEntity()->id(),
      '#exclude_markup' => FALSE,
      '#default_value' => $settings['preview_excluded_elements'],
    ];
    $form['preview_settings']['preview_container']['elements']['preview_exclude_empty'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude empty elements'),
      '#return_value' => TRUE,
      '#default_value' => $settings['preview_exclude_empty'],
    ];
    $form['preview_settings']['preview_container']['elements']['preview_exclude_empty_checkbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude unselected checkboxes'),
      '#return_value' => TRUE,
      '#default_value' => $settings['preview_exclude_empty_checkbox'],
    ];
    $form['preview_settings']['preview_container']['preview_attributes'] = [
      '#type' => 'details',
      '#title' => $this->t('Preview attributes'),
      '#open' => TRUE,
    ];
    $form['preview_settings']['preview_container']['preview_attributes']['preview_attributes'] = [
      '#type' => 'webform_element_attributes',
      '#title' => $this->t('Preview'),
      '#classes' => $this->config('webform.settings')->get('settings.preview_classes'),
      '#default_value' => $settings['preview_attributes'],
    ];
    $form['preview_settings']['preview_container']['token_tree_link'] = $this->tokenManager->buildTreeElement();

    // File settings.
    $form['file_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('File settings'),
      '#open' => TRUE,
      '#access' => $webform->hasManagedFile(),
    ];
    $form['file_settings']['form_file_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('File upload limit'),
      '#description' => $this->t('Enter a value like "512" (bytes), "80 KB" (kilobytes) or "50 MB" (megabytes) in order to set the file upload limit for this form.'),
      '#element_validate' => [['\Drupal\webform\Form\AdminConfig\WebformAdminConfigElementsForm', 'validateMaxFilesize']],
      '#size' => 10,
      '#default_value' => $settings['form_file_limit'],
    ];

    // Custom settings.
    $properties = WebformElementHelper::getProperties($webform->getElementsDecoded());
    // Set default properties.
    $properties += [
      '#method' => '',
      '#action' => '',
    ];
    $form['custom_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Form custom settings'),
      '#open' => array_filter($properties) ? TRUE : FALSE,
      '#access' => !$this->moduleHandler->moduleExists('webform_ui') || $this->currentUser()->hasPermission('edit webform source'),
    ];
    $form['custom_settings']['method'] = [
      '#type' => 'select',
      '#title' => $this->t('Form method'),
      '#description' => $this->t('The HTTP method with which the form will be submitted.') . '<br /><br />' .
        '<em>' . $this->t('Selecting a custom POST or GET method will automatically disable wizards, previews, drafts, submissions, limits, purging, confirmations, emails, computed elements, and handlers.') . '</em>',
      '#options' => [
        '' => $this->t('POST (Default)'),
        'post' => $this->t('POST (Custom)'),
        'get' => $this->t('GET (Custom)'),
      ],
      '#default_value' => $properties['#method'],
    ];
    $form['custom_settings']['method_message'] = [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t("Please make sure this webform's action URL or path is setup to handle the webform's submission."),
      '#states' => [
        'invisible' => [
          ':input[name="method"]' => ['value' => ''],
        ],
      ],
    ];

    $form['custom_settings']['action'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form action'),
      '#description' => $this->t('The URL or path to which the webform will be submitted.'),
      '#states' => [
        'invisible' => [
          ':input[name="method"]' => ['value' => ''],
        ],
        'optional' => [
          ':input[name="method"]' => ['value' => ''],
        ],
      ],
      '#default_value' => $properties['#action'],
    ];
    // Unset properties that are webform settings.
    unset(
      $properties['#method'],
      $properties['#action'],
      $properties['#novalidate'],
      $properties['#attributes']
    );
    $form['custom_settings']['custom'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Form custom properties'),
      '#description' =>
        $this->t('Properties do not have to prepended with a hash (#) character, the hash character will be automatically added to the custom properties.') .
        '<br /><br />' .
        $this->t('These properties and callbacks are not allowed: @properties.', ['@properties' => WebformArrayHelper::toString(WebformArrayHelper::addPrefix(WebformElementHelper::$ignoredProperties))]),
      '#default_value' => WebformArrayHelper::removePrefix($properties),
    ];

    $this->tokenManager->elementValidate($form);

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if ($values['status'] === WebformInterface::STATUS_SCHEDULED) {
      // Require open or close dates.
      if (empty($values['open']) && empty($values['close'])) {
        $form_state->setErrorByName('status', $this->t('Please enter an open or close date'));
      }
      // Make sure open date is not after close date.
      if (!empty($values['open']) && !empty($values['close']) && ($values['open'] > $values['close'])) {
        $form_state->setErrorByName('open', $this->t("The webform's close date cannot be before the open date"));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    // Set open and close date/time.
    $webform->set('open', NULL);
    $webform->set('close', NULL);
    if ($values['status'] === WebformInterface::STATUS_SCHEDULED) {
      // Massage open/close dates.
      // @see \Drupal\webform\Plugin\Field\FieldWidget\WebformEntityReferenceAutocompleteWidget::massageFormValues
      // @see \Drupal\datetime\Plugin\Field\FieldWidget\DateTimeWidgetBase::massageFormValues
      $states = ['open', 'close'];
      foreach ($states as $state) {
        if (!empty($values[$state]) && $values[$state] instanceof DrupalDateTime) {
          $webform->set($state, WebformDateHelper::formatStorage($values[$state]));
        }
      }
    }

    // Set custom properties, class, and style.
    $elements = $webform->getElementsDecoded();
    $elements = WebformElementHelper::removeProperties($elements);

    $properties = [];

    // Unset custom method and action.
    unset(
      $properties['#method'],
      $properties['#action']
    );

    // Set custom method and action.
    if (!empty($values['method'])) {
      $properties['#method'] = $values['method'];
      if (!empty($values['action'])) {
        $properties['#action'] = $values['action'];
      }
    }

    // Set custom properties.
    if (!empty($values['custom'])) {
      $properties += WebformArrayHelper::addPrefix($values['custom']);
    }

    // Set custom attributions.
    if (!empty($values['attributes'])) {
      $properties['#attributes'] = $values['attributes'];
    }

    // Prepend form properties to elements.
    $elements = $properties + $elements;

    // Save elements.
    $webform->setElements($elements);

    // Remove custom properties and attributes.
    unset(
      $values['method'],
      $values['action'],
      $values['attributes'],
      $values['custom']
    );

    // Remove main properties.
    unset(
      $values['status'],
      $values['open'],
      $values['close']
    );

    // Remove *_disabled form behavior properties.
    $form_behaviors = $this->getFormBehaviors();
    foreach ($form_behaviors as $form_behavior_key => $form_behavior_element) {
      if (isset($form_behavior_element['all_description'])) {
        unset($values[$form_behavior_key . '_disabled']);
      }
    }

    // Set settings.
    $webform->setSettings($values);

    parent::save($form, $form_state);
  }

  /**
   * Get form behaviors.
   *
   * @return array
   *   An associative array containing form behaviors.
   */
  protected function getFormBehaviors() {
    return [
      // Form.
      'form_reset' => [
        'group' => $this->t('Form'),
        'title' => $this->t('Display reset button'),
        'form_description' => $this->t("If checked, users will be able to reset a form and restart multi-step wizards. Current drafts will be deleted when the form is reset."),
      ],
      'form_submit_once' => [
        'group' => $this->t('Form'),
        'title' => $this->t('Prevent duplicate submissions'),
        'all_description' => $this->t('Submit button is disabled immediately after it is clicked for all forms.'),
        'form_description' => $this->t('If checked, the submit button will be disabled immediately after it is clicked.'),
      ],

      // Navigation.
      'form_disable_back' => [
        'group' => $this->t('Navigation'),
        'title' => $this->t('Disable back button'),
        'all_description' => $this->t('Back button is disabled for all forms.'),
        'form_description' => $this->t("If checked, users will not be allowed to navigate back to the form using the browser's back button."),
      ],
      'form_submit_back' => [
        'group' => $this->t('Navigation'),
        'title' => $this->t('Submit previous page when browser back button is clicked'),
        'all_description' => $this->t('Browser back button submits the previous page for all forms.'),
        'form_description' => $this->t("If checked, the browser back button will submit the previous page and navigate back emulating the behaviour of user clicking a wizard or preview page's back button."),
      ],
      'form_unsaved' => [
        'group' => $this->t('Navigation'),
        'title' => $this->t('Warn users about unsaved changes'),
        'all_description' => $this->t('Unsaved warning is enabled for all forms.'),
        'form_description' => $this->t('If checked, users will be displayed a warning message when they navigate away from a form with unsaved changes.'),
      ],

      // Validation.
      'form_novalidate' => [
        'group' => $this->t('Validation'),
        'title' => $this->t('Disable client-side validation'),
        'all_description' => $this->t('Client-side validation is disabled for all forms.'),
        'form_description' => $this->t('If checked, the <a href=":href">novalidate</a> attribute, which disables client-side validation, will be added to this form.', [':href' => 'http://www.w3schools.com/tags/att_form_novalidate.asp']),
      ],
      'form_disable_inline_errors' => [
        'group' => $this->t('Validation'),
        'title' => $this->t('Disable inline form errors'),
        'all_description' => $this->t('Inline form errors is disabled for all forms.'),
        'form_description' => $this->t('If checked, <a href=":href">inline form errors</a> will be disabled for this form.', [':href' => 'https://www.drupal.org/docs/8/core/modules/inline-form-errors/inline-form-errors-module-overview']),
      ],
      'form_required' => [
        'group' => $this->t('Validation'),
        'title' => $this->t('Display required indicator'),
        'all_description' => $this->t('Required indicator is displayed on all forms.'),
        'form_description' => $this->t('If checked, a required elements indicator will be added to this webform.'),
      ],

      // Elements.
      'form_autofocus' => [
        'group' => $this->t('Elements'),
        'title' => $this->t('Autofocus the first element'),
        'form_description' => $this->t('If checked, the first visible and enabled form element will be focused when adding a new submission.'),
      ],
      'form_disable_autocomplete' => [
        'group' => $this->t('Elements'),
        'title' => $this->t('Disable autocompletion'),
        'form_description' => $this->t('If checked, the <a href=":href">autocomplete</a> attribute will be set to off, which disables autocompletion for all form elements.', [':href' => 'http://www.w3schools.com/tags/att_form_autocomplete.asp']),
      ],
      'form_details_toggle' => [
        'group' => $this->t('Elements'),
        'title' => $this->t('Display collapse/expand all details link'),
        'all_description' => $this->t('Expand/collapse all (details) link is automatically added to all forms.'),
        'form_description' => $this->t('If checked, an expand/collapse all (details) link will be added to this webform when there are two or more details elements available on the webform.'),
      ],

      // Prepopulate.
      'form_prepopulate' => [
        'group' => $this->t('Prepopulate'),
        'title' => $this->t('Allow all elements to be populated using query string parameters'),
        'form_description' => $this->t("If checked, all elements can be populated using query string parameters. For example, appending ?name=John+Smith to a webform's URL would set the 'name' element's default value to 'John Smith'. Please note that individual elements can also have prepopulation enabled."),
      ],
      'form_prepopulate_source_entity' => [
        'group' => $this->t('Prepopulate'),
        'title' => $this->t('Allow source entity to be populated using query string parameters'),
        'form_description' => $this->t("If checked, source entity can be populated using query string parameters. For example, appending ?source_entity_type=node&source_entity_id=1 to a webform's URL would set a submission's 'Submitted to' value to 'node:1'."),
      ],
      'form_prepopulate_source_entity_required' => [
        'group' => $this->t('Prepopulate'),
        'title' => $this->t('Require source entity to be populated using query string parameters'),
        'form_description' => $this->t("If checked, source entity must be populated using query string parameters."),
      ],
    ];
  }

}
