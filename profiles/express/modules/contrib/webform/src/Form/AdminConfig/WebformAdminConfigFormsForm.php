<?php

namespace Drupal\webform\Form\AdminConfig;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\filter\Entity\FilterFormat;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\WebformAddonsManagerInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Drupal\webform\WebformThirdPartySettingsManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure webform admin settings for forms.
 */
class WebformAdminConfigFormsForm extends WebformAdminConfigBaseForm {

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
   * The webform storage.
   *
   * @var \Drupal\webform\WebformEntityStorageInterface
   */
  protected $webformStorage;

  /**
   * The webform third party settings manager.
   *
   * @var \Drupal\webform\WebformThirdPartySettingsManagerInterface
   */
  protected $thirdPartySettingsManager;

  /**
   * The webform add-ons manager.
   *
   * @var \Drupal\webform\WebformAddonsManagerInterface
   */
  protected $addonsManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_admin_config_forms_form';
  }

  /**
   * Constructs a WebformAdminConfigFormsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   * @param \Drupal\webform\WebformThirdPartySettingsManagerInterface $third_party_settings_manager
   *   The webform third party settings manager.
   * @param \Drupal\webform\WebformAddonsManagerInterface $addons_manager
   *   The webform add-ons manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, WebformTokenManagerInterface $token_manager, WebformThirdPartySettingsManagerInterface $third_party_settings_manager, WebformAddonsManagerInterface $addons_manager) {
    parent::__construct($config_factory);
    $this->webformStorage = $entity_type_manager->getStorage('webform');
    $this->moduleHandler = $module_handler;
    $this->tokenManager = $token_manager;
    $this->thirdPartySettingsManager = $third_party_settings_manager;
    $this->addonsManager = $addons_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('webform.token_manager'),
      $container->get('webform.third_party_settings_manager'),
      $container->get('webform.addons_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('webform.settings');
    $settings = $config->get('settings');

    // Forms overview settings.
    $t_args = [
      ':href' => Url::fromRoute('entity.webform.collection')->toString(),
    ];
    $form['filter_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Form overview settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['filter_settings']['filter_category'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter webforms default category'),
      '#description' => $this->t('Select the filter webforms default category selected on the <a href=":href">webform overview page</a>.', $t_args),
      '#options' => $this->webformStorage->getCategories(FALSE),
      '#empty_option' => $this->t('Show all webforms'),
      '#parents' => ['form', 'filter_category'],
      '#default_value' => $config->get('form.filter_category'),
    ];
    $form['filter_settings']['filter_state'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter webforms default state'),
      '#description' => $this->t('Select the filter webforms default state selected on the <a href=":href">webform overview page</a>.', $t_args),
      '#options' => [
        WebformInterface::STATUS_OPEN => $this->t('Open'),
        WebformInterface::STATUS_CLOSED => $this->t('Closed'),
        WebformInterface::STATUS_SCHEDULED => $this->t('Scheduled'),
        WebformInterface::STATUS_ARCHIVED => $this->t('Archived'),
      ],
      '#empty_option' => $this->t('All'),
      '#parents' => ['form', 'filter_state'],
      '#default_value' => $config->get('form.filter_state'),
    ];

    // Page settings.
    $form['page_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Form URL path settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['page_settings']['default_page_base_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default base path for webform URLs'),
      '#description' => $this->t('Leave blank to disable the automatic generation of URL aliases for all webforms.'),
      '#default_value' => $settings['default_page_base_path'],
    ];
    $form['page_settings']['default_page_base_path_message'] = [
      '#type' => 'webform_message',
      '#message_message' => $this->t('All URL aliases for all webforms have to be manually created.'),
      '#message_type' => 'warning',
      '#states' => [
        'visible' => [
          ':input[name="page_settings[default_page_base_path]"]' => ['empty' => TRUE],
        ],
      ],
    ];

    // Form settings.
    $form['form_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Form general settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['form_settings']['default_status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Default status'),
      '#default_value' => $settings['default_status'],
      '#options' => [
        WebformInterface::STATUS_OPEN => $this->t('Open'),
        WebformInterface::STATUS_CLOSED => $this->t('Closed'),
      ],
      '#options_display' => 'side_by_side',
    ];
    $form['form_settings']['default_form_open_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default open message'),
      '#default_value' => $settings['default_form_open_message'],
    ];
    $form['form_settings']['default_form_close_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default closed message'),
      '#default_value' => $settings['default_form_close_message'],
    ];
    $form['form_settings']['default_form_exception_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default exception message'),
      '#required' => TRUE,
      '#default_value' => $settings['default_form_exception_message'],
    ];
    $form['form_settings']['default_form_confidential_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default confidential message'),
      '#required' => TRUE,
      '#default_value' => $settings['default_form_confidential_message'],
    ];
    $form['form_settings']['default_form_access_denied_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default access denied message'),
      '#required' => TRUE,
      '#default_value' => $settings['default_form_access_denied_message'],
    ];
    $form['form_settings']['default_form_required_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default required indicator label'),
      '#required' => TRUE,
      '#size' => 20,
      '#default_value' => $settings['default_form_required_label'],
    ];
    $form['form_settings']['default_submit_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default submit button label'),
      '#required' => TRUE,
      '#size' => 20,
      '#default_value' => $settings['default_submit_button_label'],
    ];
    $form['form_settings']['default_reset_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default reset button label'),
      '#required' => TRUE,
      '#size' => 20,
      '#default_value' => $settings['default_reset_button_label'],
    ];
    $form['form_settings']['form_classes'] = [
      '#type' => 'webform_codemirror',
      '#title' => $this->t('Form CSS classes'),
      '#description' => $this->t('A list of classes that will be provided in the "Form CSS classes" dropdown. Enter one or more classes on each line. These styles should be available in your theme\'s CSS file.'),
      '#default_value' => $settings['form_classes'],
    ];
    $form['form_settings']['button_classes'] = [
      '#type' => 'webform_codemirror',
      '#title' => $this->t('Button CSS classes'),
      '#description' => $this->t('A list of classes that will be provided in "Button CSS classes" dropdown. Enter one or more classes on each line. These styles should be available in your theme\'s CSS file.'),
      '#default_value' => $settings['button_classes'],
    ];
    $form['form_settings']['token_tree_link'] = $this->tokenManager->buildTreeElement();

    // Form Behaviors.
    $form['form_behaviors'] = [
      '#type' => 'details',
      '#title' => $this->t('Form behaviors'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $behavior_elements = [
      // Form.
      'default_form_submit_once' => [
        'group' => $this->t('Form'),
        'title' => $this->t('Prevent duplicate submissions for all webforms'),
        'description' => $this->t('If checked, the submit button will be disabled immediately after it is clicked.'),
      ],
      // Navigation.
      'default_form_disable_back' => [
        'group' => $this->t('Navigation'),
        'title' => $this->t('Disable back button for all webforms'),
        'description' => $this->t("If checked, users will not be allowed to navigate back to the webform using the browser's back button."),
      ],
      'default_form_submit_back' => [
        'group' => $this->t('Navigation'),
        'title' => $this->t('Submit previous page when browser back button is clicked for all webforms'),
        'description' => $this->t("If checked, the browser back button will submit the previous page and navigate back emulating the behaviour of user clicking a wizard or preview page's back button."),
      ],
      'default_form_unsaved' => [
        'group' => $this->t('Navigation'),
        'title' => $this->t('Warn users about unsaved changes for all webforms'),
        'description' => $this->t('If checked, users will be displayed a warning message when they navigate away from a webform with unsaved changes.'),
      ],
      // Validation.
      'default_form_novalidate' => [
        'group' => $this->t('Validation'),
        'title' => $this->t('Disable client-side validation for all webforms'),
        'description' => $this->t('If checked, the <a href=":href">novalidate</a> attribute, which disables client-side validation, will be added to all webforms.', [':href' => 'http://www.w3schools.com/tags/att_form_novalidate.asp']),
      ],
      'default_form_disable_inline_errors' => [
        'group' => $this->t('Validation'),
        'title' => $this->t('Disable inline form errors for all webforms'),
        'description' => $this->t('If checked, <a href=":href">inline form errors</a>  will be disabled for all webforms.', [':href' => 'https://www.drupal.org/docs/8/core/modules/inline-form-errors/inline-form-errors-module-overview']),
        'access' => \Drupal::moduleHandler()->moduleExists('inline_form_errors'),
      ],
      'default_form_required' => [
        'group' => $this->t('Validation'),
        'title' => $this->t('Display required indicator on all webforms'),
        'description' => $this->t('If checked, a required elements indicator will be added to all webforms.'),
      ],
      // Elements.
      'default_form_details_toggle' => [
        'group' => $this->t('Elements'),
        'title' => $this->t('Display collapse/expand all details link on all webforms'),
        'description' => $this->t('If checked, an expand/collapse all details link will be added to all webforms which contain two or more details elements.'),
      ],
    ];
    foreach ($behavior_elements as $behavior_key => $behavior_element) {
      // Add group.
      if (isset($behavior_element['group'])) {
        $group = (string) $behavior_element['group'];
        if (!isset($form['form_behaviors'][$group])) {
          $form['form_behaviors'][$group] = [
            '#markup' => $group,
            '#prefix' => '<div><strong>',
            '#suffix' => '</strong></div>',
          ];
        }
      }
      // Add behavior checkbox.
      $form['form_behaviors'][$behavior_key] = [
        '#type' => 'checkbox',
        '#title' => $behavior_element['title'],
        '#description' => $behavior_element['description'],
        '#return_value' => TRUE,
        '#default_value' => $settings[$behavior_key],
      ];
      if (isset($behavior_element['access'])) {
        $form['form_behaviors'][$behavior_key]['#access'] = $behavior_element['access'];
      }
    }

    // Wizard settings.
    $form['wizard_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Form wizard settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['wizard_settings']['default_wizard_prev_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default wizard previous page button label'),
      '#required' => TRUE,
      '#size' => 20,
      '#default_value' => $settings['default_wizard_prev_button_label'],
    ];
    $form['wizard_settings']['default_wizard_next_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default wizard next page button label'),
      '#required' => TRUE,
      '#size' => 20,
      '#default_value' => $settings['default_wizard_next_button_label'],
    ];
    $form['wizard_settings']['default_wizard_start_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default wizard start label'),
      '#required' => TRUE,
      '#size' => 20,
      '#default_value' => $settings['default_wizard_start_label'],
    ];
    $form['wizard_settings']['default_wizard_confirmation_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default wizard end label'),
      '#required' => TRUE,
      '#size' => 20,
      '#default_value' => $settings['default_wizard_confirmation_label'],
    ];

    // Preview settings.
    $form['preview_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Form preview settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['preview_settings']['default_preview_next_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default preview button label'),
      '#required' => TRUE,
      '#size' => 20,
      '#default_value' => $settings['default_preview_next_button_label'],
    ];
    $form['preview_settings']['default_preview_prev_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default preview previous page button label'),
      '#required' => TRUE,
      '#size' => 20,
      '#default_value' => $settings['default_preview_prev_button_label'],
    ];
    $form['preview_settings']['default_preview_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default preview label'),
      '#required' => TRUE,
      '#default_value' => $settings['default_preview_label'],
    ];
    $form['preview_settings']['default_preview_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default preview page title'),
      '#required' => TRUE,
      '#default_value' => $settings['default_preview_title'],
    ];
    $form['preview_settings']['default_preview_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default preview message'),
      '#description' => $this->t('Leave blank to not automatically include a preview message on all forms.'),
      '#default_value' => $settings['default_preview_message'],
    ];
    $form['preview_settings']['preview_classes'] = [
      '#type' => 'webform_codemirror',
      '#title' => $this->t('Preview CSS classes'),
      '#description' => $this->t('A list of classes that will be provided in the "Preview CSS classes" dropdown. Enter one or more classes on each line. These styles should be available in your theme\'s CSS file.'),
      '#default_value' => $config->get('settings.preview_classes'),
    ];
    $form['preview_settings']['token_tree_link'] = $this->tokenManager->buildTreeElement();

    // Confirmation settings.
    $form['confirmation_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Form confirmation settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['confirmation_settings']['default_confirmation_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default confirmation message'),
      '#default_value' => $settings['default_confirmation_message'],
    ];
    $form['confirmation_settings']['default_confirmation_back_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default confirmation back label'),
      '#required' => TRUE,
      '#default_value' => $settings['default_confirmation_back_label'],
    ];
    $form['confirmation_settings']['confirmation_classes'] = [
      '#type' => 'webform_codemirror',
      '#title' => $this->t('Confirmation CSS classes'),
      '#description' => $this->t('A list of classes that will be provided in the "Confirmation CSS classes" dropdown. Enter one or more classes on each line. These styles should be available in your theme\'s CSS file.'),
      '#default_value' => $settings['confirmation_classes'],
    ];
    $form['confirmation_settings']['confirmation_back_classes'] = [
      '#type' => 'webform_codemirror',
      '#title' => $this->t('Confirmation back link CSS classes'),
      '#description' => $this->t('A list of classes that will be provided in the "Confirmation back link CSS classes" dropdown. Enter one or more classes on each line. These styles should be available in your theme\'s CSS file.'),
      '#default_value' => $settings['confirmation_back_classes'],
    ];
    $form['confirmation_settings']['token_tree_link'] = $this->tokenManager->buildTreeElement();

    // Ajax settings.
    $form['ajax_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Form Ajax settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['ajax_settings']['default_ajax'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Ajax for all webforms'),
      '#description' => $this->t('If checked, paging, saving of drafts, previews, submissions, and confirmations will not initiate a page refresh on all webforms.'),
      '#return_value' => TRUE,
      '#default_value' => $settings['default_ajax'],
    ];
    $form['ajax_settings']['default_ajax_progress_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Ajax progress type'),
      '#description' => $this->t("Select the default progress indicator displayed when Ajax is triggered."),
      '#options' => [
        'throbber' => $this->t('Throbber'),
        'fullscreen' => $this->t('Full screen'),
      ],
      '#default_value' => $settings['default_ajax_progress_type'],
      '#required' => TRUE,
    ];
    $form['ajax_settings']['default_ajax_effect'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Ajax effect'),
      '#description' => $this->t("Select the default effect displayed when Ajax is triggered."),
      '#options' => [
        'none' => $this->t('None'),
        'fade' => $this->t('Fade'),
        'slide' => $this->t('Slide'),
      ],
      '#default_value' => $settings['default_ajax_effect'],
      '#required' => TRUE,
    ];
    $form['ajax_settings']['default_ajax_speed'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Default Ajax speed'),
      '#description' => $this->t("Select the default effect speed."),
      '#other__type' => 'number',
      '#other__placeholder' => '',
      '#other__field_suffix' => $this->t('milliseconds'),
      '#options' => [
        '500' => $this->t('@number milliseconds', ['@number' => '500']),
        '1000' => $this->t('@number milliseconds', ['@number' => '1000']),
        '1500' => $this->t('@number milliseconds', ['@number' => '1500']),
      ],
      '#states' => [
        'visible' => [
          ':input[name="ajax_settings[default_ajax_effect]"]' => ['!value' => 'none'],
        ],
      ],
      '#default_value' => $settings['default_ajax_speed'],
    ];

    // Dialog settings.
    $form['dialog_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Form dialog settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['dialog_settings']['dialog_options'] = [
      '#title' => $this->t('Dialog options'),
      '#description' => [
        '#markup' => $this->t('Enter preset dialog options available to all webforms.'),
        'options' => [
          '#theme' => 'item_list',
          '#items' => [
            $this->t('Name must be lower-case and contain only letters, numbers, and underscores.'),
            $this->t('Width and height are optional.'),
          ],
        ],
      ],
      '#type' => 'webform_multiple',
      '#key' => 'name',
      '#header' => [
        ['data' => $this->t('Machine name'), 'width' => '40%'],
        ['data' => $this->t('Title'), 'width' => '40%'],
        ['data' => $this->t('Width'), 'width' => '10%'],
        ['data' => $this->t('Height'), 'width' => '10%'],
      ],
      '#element' => [
        'name' => [
          '#type' => 'textfield',
          '#title' => $this->t('Dialog machine name'),
          '#title_display' => 'invisible',
          '#placeholder' => $this->t('Enter machine name…'),
          '#pattern' => '^[a-z0-9_]*$',
          '#error_no_message' => TRUE,
        ],
        'title' => [
          '#type' => 'textfield',
          '#title' => $this->t('Dialog title'),
          '#placeholder' => $this->t('Enter title…'),
          '#title_display' => 'invisible',
          '#error_no_message' => TRUE,
        ],
        'width' => [
          '#type' => 'number',
          '#title' => $this->t('Dialog width'),
          '#title_display' => 'invisible',
          '#field_suffix' => 'px',
          '#error_no_message' => TRUE,
        ],
        'height' => [
          '#type' => 'number',
          '#title' => $this->t('Dialog height'),
          '#title_display' => 'invisible',
          '#field_suffix' => 'px',
          '#error_no_message' => TRUE,
        ],
      ],
      '#error_no_message' => TRUE,
      '#default_value' => $settings['dialog_options'],
      '#parents' => ['dialog_settings', 'dialog_options'],
    ];
    $form['dialog_settings']['dialog'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable site-wide dialog support'),
      '#description' => $this->t('If checked, the webform dialog library will be added to every page on your website, this allows any webform to be opened in a modal dialog.')
        . '<br /><br />'
        . $this->t('Webform specific dialog links will be included on all webform settings form.'),
      '#return_value' => TRUE,
      '#default_value' => $settings['dialog'],
    ];
    $form['dialog_settings']['dialog_messages'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="dialog_settings[dialog]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    // Display warning when text formats do not support adding the class
    // attribute to links.
    if ($this->moduleHandler->moduleExists('filter')) {
      /** @var \Drupal\filter\FilterFormatInterface[] $filter_formats */
      $filter_formats = FilterFormat::loadMultiple();
      $dialog_not_allowed = [];
      foreach ($filter_formats as $filter_format) {
        $html_restrictions = $filter_format->getHtmlRestrictions();
        if ($html_restrictions && isset($html_restrictions['allowed']) && isset($html_restrictions['allowed']['a']) && !isset($html_restrictions['allowed']['a']['class'])) {
          $dialog_not_allowed[] = $filter_format->label();
        }
      }
      if ($dialog_not_allowed) {
        $t_args = [
          '@labels' => WebformArrayHelper::toString($dialog_not_allowed),
          '@tag' => '<a href hreflang class>',
          ':href' => Url::fromRoute('filter.admin_overview')->toString(),
        ];
        $form['dialog_settings']['dialog_messages']['filter_formats_message'] = [
          '#type' => 'webform_message',
          '#message_message' => $this->t('<strong>IMPORTANT:</strong> To insert dialog links using the @labels <a href=":href">text formats</a> the @tag must be added to the allowed HTML tags.', $t_args),
          '#message_type' => 'warning',
        ];
      }
    }
    // Display install link module message.
    if (!$this->moduleHandler->moduleExists('editor_advanced_link') && !$this->moduleHandler->moduleExists('menu_link_attributes')) {
      $t_args = [
        ':editor_advanced_link_href' => 'https://www.drupal.org/project/editor_advanced_link',
        ':menu_link_attributes_href' => 'https://www.drupal.org/project/menu_link_attributes',
      ];
      $form['dialog_settings']['dialog_messages']['module_message'] = [
        '#type' => 'webform_message',
        '#message_message' => $this->t('To add the .webform-dialog class to a link\'s attributes, please use the <a href=":editor_advanced_link_href">D8 Editor Advanced link</a> or <a href=":menu_link_attributes_href">Menu Link Attributes</a> modules.', $t_args),
        '#message_type' => 'info',
        '#message_close' => TRUE,
        '#message_storage' => WebformMessage::STORAGE_SESSION,
      ];
    }

    // Third party settings.
    $form['third_party_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Third party settings'),
      '#description' => $this->t('Third party settings allow contrib and custom modules to define global settings that are applied to all webforms and submissions.'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $this->thirdPartySettingsManager->alter('webform_admin_third_party_settings_form', $form, $form_state);

    if (!Element::children($form['third_party_settings'])) {
      $form['third_party_settings']['message'] = [
        '#type' => 'webform_message',
        '#message_message' => $this->t('There are no third party settings available. Please install a contributed module that integrates with the Webform module.'),
        '#message_type' => 'info',
      ];
      $form['third_party_settings']['supported'] = [
        'title' => [
          '#markup' => $this->t('Supported modules'),
          '#prefix' => '<h3>',
          '#suffix' => '</h3>',
        ],
        'modules' => [
          '#theme' => 'admin_block_content',
          '#content' => $this->addonsManager->getThirdPartySettings(),
        ],
      ];
    }
    else {
      ksort($form['third_party_settings']);
    }

    $this->tokenManager->elementValidate($form);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $form_state->getValue('page_settings')
      + $form_state->getValue('form_settings')
      + $form_state->getValue('form_behaviors')
      + $form_state->getValue('wizard_settings')
      + $form_state->getValue('preview_settings')
      + $form_state->getValue('confirmation_settings')
      + $form_state->getValue('ajax_settings')
      + $form_state->getValue('dialog_settings');

    // Track if we need to trigger an update of all webform paths
    // because the 'default_page_base_path' changed.
    $update_paths = ($settings['default_page_base_path'] != $this->config('webform.settings')->get('settings.default_page_base_path')) ? TRUE : FALSE;

    // Filter empty dialog options.
    foreach ($settings['dialog_options'] as $dialog_name => $dialog_options) {
      $settings['dialog_options'][$dialog_name] = array_filter($dialog_options);
    }

    // Update config and submit form.
    $config = $this->config('webform.settings');
    $config->set('settings', $settings + $config->get('settings'));
    $config->set('form', $form_state->getValue('form') ?: []);
    $config->set('third_party_settings', $form_state->getValue('third_party_settings') ?: []);
    parent::submitForm($form, $form_state);

    /* Update paths */

    if ($update_paths) {
      /** @var \Drupal\webform\WebformInterface[] $webforms */
      $webforms = Webform::loadMultiple();
      foreach ($webforms as $webform) {
        $webform->updatePaths();
      }
    }
  }

}
