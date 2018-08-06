<?php

namespace Drupal\webform\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\WebformElementManagerInterface;
use Drupal\webform\WebformLibrariesManagerInterface;
use Drupal\webform\WebformSubmissionExporterInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure webform admin settings for this site.
 */
class WebformAdminSettingsForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The webform element manager.
   *
   * @var \Drupal\webform\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * The webform submission exporter.
   *
   * @var \Drupal\webform\WebformSubmissionExporterInterface
   */
  protected $submissionExporter;

  /**
   * The token manager.
   *
   * @var \Drupal\webform\WebformTranslationManagerInterface
   */
  protected $tokenManager;

  /**
   * The libraries manager.
   *
   * @var \Drupal\webform\WebformLibrariesManagerInterface
   */
  protected $librariesManager;

  /**
   * An array of element types.
   *
   * @var array
   */
  protected $elementTypes;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['webform.settings'];
  }

  /**
   * Constructs a WebformAdminSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $third_party_settings_manager
   *   The module handler.
   * @param \Drupal\webform\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\WebformSubmissionExporterInterface $submission_exporter
   *   The webform submission exporter.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The token manager.
   * @param \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager
   *   The libraries manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $third_party_settings_manager, WebformElementManagerInterface $element_manager, WebformSubmissionExporterInterface $submission_exporter, WebformTokenManagerInterface $token_manager, WebformLibrariesManagerInterface $libraries_manager) {
    parent::__construct($config_factory);
    $this->moduleHandler = $third_party_settings_manager;
    $this->elementManager = $element_manager;
    $this->submissionExporter = $submission_exporter;
    $this->tokenManager = $token_manager;
    $this->librariesManager = $libraries_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('plugin.manager.webform.element'),
      $container->get('webform_submission.exporter'),
      $container->get('webform.token_manager'),
      $container->get('webform.libraries_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('webform.settings');
    $settings = $config->get('settings');
    $element_plugins = $this->elementManager->getInstances();

    // Page.
    $form['page'] = [
      '#type' => 'details',
      '#title' => $this->t('Page default settings'),
      '#tree' => TRUE,
    ];
    $form['page']['default_page_base_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default base path for webform URLs'),
      '#required' => TRUE,
      '#default_value' => $config->get('settings.default_page_base_path'),
    ];

    // Form.
    $form['form'] = [
      '#type' => 'details',
      '#title' => $this->t('Form default settings'),
      '#tree' => TRUE,
    ];
    $form['form']['default_form_open_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default open message'),
      '#required' => TRUE,
      '#default_value' => $config->get('settings.default_form_open_message'),
    ];
    $form['form']['default_form_close_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default closed message'),
      '#required' => TRUE,
      '#default_value' => $config->get('settings.default_form_close_message'),
    ];
    $form['form']['default_form_exception_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default exception message'),
      '#required' => TRUE,
      '#default_value' => $config->get('settings.default_form_exception_message'),
    ];
    $form['form']['default_form_confidential_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default confidential message'),
      '#required' => TRUE,
      '#default_value' => $config->get('settings.default_form_confidential_message'),
    ];
    $form['form']['default_form_submit_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default submit button label'),
      '#required' => TRUE,
      '#size' => 20,
      '#default_value' => $settings['default_form_submit_label'],
    ];
    $form['form']['form_classes'] = [
      '#type' => 'webform_codemirror',
      '#title' => $this->t('Form CSS classes '),
      '#description' => $this->t('A list of classes that will be provided in the "Form CSS classes " dropdown. Enter one or more classes on each line. These styles should be available in your theme\'s CSS file.'),
      '#default_value' => $config->get('settings.form_classes'),
    ];
    $form['form']['button_classes'] = [
      '#type' => 'webform_codemirror',
      '#title' => $this->t('Button CSS classes'),
      '#description' => $this->t('A list of classes that will be provided in "Button CSS classes" dropdown. Enter one or more classes on each line. These styles should be available in your theme\'s CSS file.'),
      '#default_value' => $config->get('settings.button_classes'),
    ];

    // Form Behaviors.
    $form['form_behaviors'] = [
      '#type' => 'details',
      '#title' => $this->t('Form default behaviors'),
      '#tree' => TRUE,
    ];
    $behavior_elements = [
      'default_form_submit_once' => [
        'title' => $this->t('Prevent duplicate submissions'),
        'description' => $this->t('If checked, the submit button will be disabled immediately after it is clicked.'),
      ],
      'default_form_disable_back' => [
        'title' => $this->t('Disable back button for all webforms'),
        'description' => $this->t('If checked, users will not be allowed to navigate back to the webform using the browsers back button.'),
      ],
      'default_form_unsaved' => [
        'title' => $this->t('Warn users about unsaved changes'),
        'description' => $this->t('If checked, users will be displayed a warning message when they navigate away from a webform with unsaved changes.'),
      ],
      'default_form_novalidate' => [
        'title' => $this->t('Disable client-side validation for all webforms'),
        'description' => $this->t('If checked, the <a href=":href">novalidate</a> attribute, which disables client-side validation, will be added to all webforms.', [':href' => 'http://www.w3schools.com/tags/att_form_novalidate.asp']),
      ],
      'default_form_details_toggle' => [
        'title' => $this->t('Display collapse/expand all details link'),
        'description' => $this->t('If checked, an expand/collapse all (details) link will be added to all webforms with two or more details elements.'),
      ],
    ];
    foreach ($behavior_elements as $behavior_key => $behavior_element) {
      $form['form_behaviors'][$behavior_key] = [
        '#type' => 'checkbox',
        '#title' => $behavior_element['title'],
        '#description' => $behavior_element['description'],
        '#return_value' => TRUE,
        '#default_value' => $settings[$behavior_key],
      ];
    }

    // Submission Behaviors.
    $form['submission_behaviors'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission default behaviors'),
      '#tree' => TRUE,
    ];
    $behavior_elements = [
      'default_submission_log' => [
        'title' => $this->t('Log all submission events for all webforms.'),
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

    // Wizard.
    $form['wizard'] = [
      '#type' => 'details',
      '#title' => $this->t('Wizard default settings'),
      '#tree' => TRUE,
    ];
    $form['wizard']['default_wizard_prev_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default wizard previous page button label'),
      '#required' => TRUE,
      '#size' => 20,
      '#default_value' => $settings['default_wizard_prev_button_label'],
    ];
    $form['wizard']['default_wizard_next_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default wizard next page button label'),
      '#required' => TRUE,
      '#size' => 20,
      '#default_value' => $settings['default_wizard_next_button_label'],
    ];
    $form['wizard']['default_wizard_start_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default wizard start label'),
      '#required' => TRUE,
      '#size' => 20,
      '#default_value' => $settings['default_wizard_start_label'],
    ];
    $form['wizard']['default_wizard_complete_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default wizard end label'),
      '#required' => TRUE,
      '#size' => 20,
      '#default_value' => $settings['default_wizard_complete_label'],
    ];

    // Preview.
    $form['preview'] = [
      '#type' => 'details',
      '#title' => $this->t('Preview default settings'),
      '#tree' => TRUE,
    ];
    $form['preview']['default_preview_next_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default preview button label'),
      '#required' => TRUE,
      '#size' => 20,
      '#default_value' => $settings['default_preview_next_button_label'],
    ];
    $form['preview']['default_preview_prev_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default preview previous page button label'),
      '#required' => TRUE,
      '#size' => 20,
      '#default_value' => $settings['default_preview_prev_button_label'],
    ];
    $form['preview']['default_preview_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default preview message'),
      '#required' => TRUE,
      '#default_value' => $settings['default_preview_message'],
    ];

    // Draft.
    $form['draft'] = [
      '#type' => 'details',
      '#title' => $this->t('Draft default settings'),
      '#tree' => TRUE,
    ];
    $form['draft']['default_draft_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default draft button label'),
      '#required' => TRUE,
      '#size' => 20,
      '#default_value' => $settings['default_draft_button_label'],
    ];
    $form['draft']['default_draft_saved_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default draft save message'),
      '#required' => TRUE,
      '#default_value' => $settings['default_draft_saved_message'],
    ];
    $form['draft']['default_draft_loaded_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default draft load message'),
      '#required' => TRUE,
      '#default_value' => $settings['default_draft_loaded_message'],
    ];

    $form['confirmation'] = [
      '#type' => 'details',
      '#title' => $this->t('Confirmation default settings'),
      '#tree' => TRUE,
    ];
    $form['confirmation']['default_confirmation_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default confirmation message'),
      '#required' => TRUE,
      '#default_value' => $config->get('settings.default_confirmation_message'),
    ];
    $form['confirmation']['default_confirmation_back_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default confirmation back label'),
      '#required' => TRUE,
      '#default_value' => $config->get('settings.default_confirmation_back_label'),
    ];
    $form['confirmation']['confirmation_classes'] = [
      '#type' => 'webform_codemirror',
      '#title' => $this->t('Confirmation CSS classes'),
      '#description' => $this->t('A list of classes that will be provided in the "Confirmation CSS classes" dropdown. Enter one or more classes on each line. These styles should be available in your theme\'s CSS file.'),
      '#default_value' => $config->get('settings.confirmation_classes'),
    ];
    $form['confirmation']['confirmation_back_classes'] = [
      '#type' => 'webform_codemirror',
      '#title' => $this->t('Confirmation back link CSS classes'),
      '#description' => $this->t('A list of classes that will be provided in the "Confirmation back link CSS classes" dropdown. Enter one or more classes on each line. These styles should be available in your theme\'s CSS file.'),
      '#default_value' => $config->get('settings.confirmation_back_classes'),
    ];

    // Limit.
    $form['limit'] = [
      '#type' => 'details',
      '#title' => $this->t('Limit default settings'),
      '#tree' => TRUE,
    ];
    $form['limit']['default_limit_total_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default total submissions limit message'),
      '#required' => TRUE,
      '#default_value' => $config->get('settings.default_limit_total_message'),
    ];
    $form['limit']['default_limit_user_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Default per user submission limit message'),
      '#required' => TRUE,
      '#default_value' => $config->get('settings.default_limit_user_message'),
    ];

    // Assets.
    $form['assets'] = [
      '#type' => 'details',
      '#title' => $this->t('Global Assets (CSS/JavaScript)'),
      '#tree' => TRUE,
    ];
    $form['assets']['css'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'css',
      '#title' => $this->t('CSS'),
      '#description' => $this->t('Enter custom CSS to be attached to all webforms.'),
      '#default_value' => $config->get('assets.css'),
    ];
    $form['assets']['javascript'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'javascript',
      '#title' => $this->t('JavaScript'),
      '#description' => $this->t('Enter custom JavaScript to be attached to all webforms.'),
      '#default_value' => $config->get('assets.javascript'),
    ];

    // Elements.
    $form['elements'] = [
      '#type' => 'details',
      '#title' => $this->t('Element default settings'),
      '#tree' => TRUE,
    ];
    $form['elements']['allowed_tags'] = [
      '#type' => 'webform_radios_other',
      '#title' => $this->t('Allowed tags'),
      '#options' => [
        'admin' => $this->t('Admin tags Excludes: script, iframe, etc...'),
        'html' => $this->t('HTML tags: Includes only @html_tags.', ['@html_tags' => WebformArrayHelper::toString(Xss::getHtmlTagList())]),
      ],
      '#other__option_label' => $this->t('Custom tags'),
      '#other__placeholder' => $this->t('Enter multiple tags delimited using spaces'),
      '#other__default_value' => implode(' ', Xss::getAdminTagList()),
      '#other__maxlength' => 1000,
      '#required' => TRUE,
      '#description' => $this->t('Allowed tags are applied to any element property that may contain HTML markup. This properties include #title, #description, #field_prefix, and #field_suffix'),
      '#default_value' => $config->get('elements.allowed_tags'),
    ];
    $form['elements']['wrapper_classes'] = [
      '#type' => 'webform_codemirror',
      '#title' => $this->t('Wrapper CSS classes'),
      '#description' => $this->t('A list of classes that will be provided in the "Wrapper CSS classes" dropdown. Enter one or more classes on each line. These styles should be available in your theme\'s CSS file.'),
      '#required' => TRUE,
      '#default_value' => $config->get('elements.wrapper_classes'),
    ];
    $form['elements']['classes'] = [
      '#type' => 'webform_codemirror',
      '#title' => $this->t('Element CSS classes'),
      '#description' => $this->t('A list of classes that will be provided in the "Element CSS classes" dropdown. Enter one or more classes on each line. These styles should be available in your theme\'s CSS file.'),
      '#required' => TRUE,
      '#default_value' => $config->get('elements.classes'),
    ];
    $form['elements']['default_description_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Default description display'),
      '#options' => [
        '' => '',
        'before' => $this->t('Before'),
        'after' => $this->t('After'),
        'invisible' => $this->t('Invisible'),
        'tooltip' => $this->t('Tooltip'),
      ],
      '#description' => $this->t('Determines the default placement of the description for all webform elements.'),
      '#default_value' => $config->get('elements.default_description_display'),
    ];
    $form['elements']['default_icheck'] = [
      '#type' => 'select',
      '#title' => $this->t('Enhance checkboxes/radio buttons using iCheck'),
      '#description' => $this->t('Replaces checkboxes/radio buttons with jQuery <a href=":href">iCheck</a> boxes.', [':href' => 'http://icheck.fronteed.com/']),
      '#options' => [
        '' => '',
        (string) $this->t('Minimal') => [
          'minimal' => $this->t('Minimal: Black'),
          'minimal-grey' => $this->t('Minimal: Grey'),
          'minimal-yellow' => $this->t('Minimal: Yellow'),
          'minimal-orange' => $this->t('Minimal: Orange'),
          'minimal-red' => $this->t('Minimal: Red'),
          'minimal-pink' => $this->t('Minimal: Pink'),
          'minimal-purple' => $this->t('Minimal: Purple'),
          'minimal-blue' => $this->t('Minimal: Blue'),
          'minimal-green' => $this->t('Minimal: Green'),
          'minimal-aero' => $this->t('Minimal: Aero'),
        ],
        (string) $this->t('Square') => [
          'square' => $this->t('Square: Black'),
          'square-grey' => $this->t('Square: Grey'),
          'square-yellow' => $this->t('Square: Yellow'),
          'square-orange' => $this->t('Square: Orange'),
          'square-red' => $this->t('Square: Red'),
          'square-pink' => $this->t('Square: Pink'),
          'square-purple' => $this->t('Square: Purple'),
          'square-blue' => $this->t('Square: Blue'),
          'square-green' => $this->t('Square: Green'),
          'square-aero' => $this->t('Square: Aero'),
        ],
        (string) $this->t('Flat') => [
          'flat' => $this->t('Flat: Black'),
          'flat-grey' => $this->t('Flat: Grey'),
          'flat-yellow' => $this->t('Flat: Yellow'),
          'flat-orange' => $this->t('Flat: Orange'),
          'flat-red' => $this->t('Flat: Red'),
          'flat-pink' => $this->t('Flat: Pink'),
          'flat-purple' => $this->t('Flat: Purple'),
          'flat-blue' => $this->t('Flat: Blue'),
          'flat-green' => $this->t('Flat: Green'),
          'flat-aero' => $this->t('Flat: Aero'),
        ],
      ],
      '#default_value' => $config->get('elements.default_icheck'),
      '#access' => $this->librariesManager->isIncluded('jquery.icheck'),
    ];
    $form['elements']['default_google_maps_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API key'),
      '#description' => $this->t('Google requires users to use a valid API key. Using the <a href="https://console.developers.google.com/apis">Google API Manager</a>, you can enable the <em>Google Maps JavaScript API</em>. That will create (or reuse) a <em>Browser key</em> which you can paste here.'),
      '#default_value' => $config->get('elements.default_google_maps_api_key'),
      '#access' => $this->librariesManager->isIncluded('jquery.geocomplete'),
    ];

    // (Excluded) Types.
    $types_header = [
      'title' => ['data' => $this->t('Title')],
      'type' => ['data' => $this->t('Type')],
    ];
    $this->elementTypes = [];
    $types_options = [];
    foreach ($element_plugins as $element_id => $element_plugin) {
      $this->elementTypes[$element_id] = $element_id;
      $types_options[$element_id] = [
        'title' => $element_plugin->getPluginLabel(),
        'type' => $element_plugin->getTypeName(),
      ];
    }
    $form['types'] = [
      '#type' => 'details',
      '#title' => $this->t('Element types'),
      '#description' => $this->t('Select available element types'),
    ];
    $form['types']['excluded_types'] = [
      '#type' => 'tableselect',
      '#header' => $types_header,
      '#options' => $types_options,
      '#required' => TRUE,
      '#default_value' => array_diff($this->elementTypes, $config->get('elements.excluded_types')),
    ];

    // File.
    $form['file'] = [
      '#type' => 'details',
      '#title' => $this->t('File upload default settings'),
      '#tree' => TRUE,
    ];
    $form['file']['file_public'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow files to be uploaded to public file system.'),
      '#description' => $this->t('Public files upload destination is dangerous for webforms that are available to anonymous and/or untrusted users.') . ' ' .
      $this->t('For more information see: <a href="@href">DRUPAL-PSA-2016-003</a>', ['@href' => 'https://www.drupal.org/psa-2016-003']),
      '#return_value' => TRUE,
      '#default_value' => $config->get('file.file_public'),
    ];
    $form['file']['default_max_filesize'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default maximum upload size'),
      '#description' => $this->t('Enter a value like "512" (bytes), "80 KB" (kilobytes) or "50 MB" (megabytes) in order to restrict the allowed file size. If left empty the file sizes will be limited only by PHP\'s maximum post and file upload sizes (current limit <strong>%limit</strong>).', ['%limit' => function_exists('file_upload_max_size') ? format_size(file_upload_max_size()) : $this->t('N/A')]),
      '#element_validate' => [[get_class($this), 'validateMaxFilesize']],
      '#size' => 10,
      '#default_value' => $config->get('file.default_max_filesize'),
    ];
    $file_types = [
      'managed_file' => 'file',
      'audio_file' => 'audio file',
      'document_file' => 'document file',
      'image_file' => 'image file',
      'video_file' => 'video file',
    ];
    foreach ($file_types as $file_type_name => $file_type_title) {
      $form['file']["default_{$file_type_name}_extensions"] = [
        '#type' => 'textfield',
        '#title' => $this->t('Default allowed @title extensions', ['@title' => $file_type_title]),
        '#description' => $this->t('Separate extensions with a space and do not include the leading dot.'),
        '#element_validate' => [[get_class($this), 'validateExtensions']],
        '#required' => TRUE,
        '#maxlength' => 256,
        '#default_value' => $config->get("file.default_{$file_type_name}_extensions"),
      ];
    }

    // Format.
    $form['format'] = [
      '#type' => 'details',
      '#title' => $this->t('Format default settings'),
      '#tree' => TRUE,
    ];
    foreach ($element_plugins as $element_id => $element_plugin) {
      // Element.
      $element_plugin_definition = $element_plugin->getPluginDefinition();
      $element_plugin_label = $element_plugin_definition['label'];
      $form['format'][$element_id] = [
        '#type' => 'details',
        '#title' => new FormattableMarkup('@label (@id)', ['@label' => $element_plugin_label, '@id' => $element_plugin->getTypeName()]),
        '#description' => $element_plugin->getPluginDescription(),
      ];
      // Element item format.
      $item_formats = $element_plugin->getItemFormats();
      foreach ($item_formats as $format_name => $format_label) {
        $item_formats[$format_name] = new FormattableMarkup('@label (@name)', ['@label' => $format_label, '@name' => $format_name]);
      }
      $item_formats = ['' => '<' . $this->t('Default') . '>'] + $item_formats;
      $item_default_format = $element_plugin->getItemDefaultFormat();
      $item_default_format_label = (isset($item_formats[$item_default_format])) ? $item_formats[$item_default_format] : $item_default_format;
      $form['format'][$element_id]['item'] = [
        '#type' => 'select',
        '#title' => $this->t('Item format'),
        '#description' => $this->t("Select how a @label element's single value is displayed.", ['@label' => $element_plugin_label]) . '<br/>' .
        $this->t('Defaults to: %value', ['%value' => $item_default_format_label]),
        '#options' => $item_formats,
        '#default_value' => $config->get("format.$element_id"),
      ];
      // Element items format.
      if ($element_plugin->supportsMultipleValues()) {
        $items_formats = $element_plugin->getItemsFormats();
        foreach ($items_formats as $format_name => $format_label) {
          $items_formats[$format_name] = new FormattableMarkup('@label (@name)', ['@label' => $format_label, '@name' => $format_name]);
        }
        $items_formats = ['' => '<' . $this->t('Default') . '>'] + $items_formats;
        $items_default_format = $element_plugin->getItemsDefaultFormat();
        $items_default_format_label = (isset($item_formats[$items_default_format])) ? $items_formats[$items_default_format] : $items_default_format;
        $form['format'][$element_id]['items'] = [
          '#type' => 'select',
          '#title' => $this->t('Items format'),
          '#description' => $this->t("Select how a @label element's multiple values are displayed.", ['@label' => $element_plugin_label]) . '<br/>' .
          $this->t('Defaults to: %value', ['%value' => $items_default_format_label]),
          '#options' => $items_formats,
          '#default_value' => $config->get("format.$element_id"),
        ];
      }
    }

    // Mail.
    $form['mail'] = [
      '#type' => 'details',
      '#title' => $this->t('Email default settings'),
      '#tree' => TRUE,
    ];
    $form['mail']['roles'] = [
      '#type' => 'webform_roles',
      '#title' => $this->t('Recipent roles'),
      '#description' => $this->t("Select roles that can be assigned to receive a webform's email. <em>Please note: Selected roles will be available to all webforms.</em>"),
      '#include_anonymous' => FALSE,
      '#default_value' => $config->get('mail.roles'),
    ];
    $form['mail']['default_from_mail'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default from email'),
      '#description' => $this->t('The default sender address for emailed webform results; often the e-mail address of the maintainer of your forms.'),
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
      '#description' => $this->t("Enter the email address that a recipient will see when they are replying to an email. Leave blank to automatically use the 'From email' address. Setting the 'Reply-to' to the 'From email' prevent emails from being flagged as spam."),
      '#default_value' => $config->get('mail.default_reply_to'),
    ];
    $form['mail']['default_return_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default return path (email)'),
      '#description' => $this->t("Enter an email address to which bounce messages are delivered. Leave blank to automatically use the 'From email' address."),
      '#default_value' => $config->get('mail.default_return_path'),
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

    // Export.
    $form['export'] = [
      '#type' => 'details',
      '#title' => $this->t('Export default settings'),
    ];
    $export_options = NestedArray::mergeDeep($config->get('export') ?: [],
      $this->submissionExporter->getValuesFromInput($form_state->getUserInput())
    );
    $export_form_state = new FormState();
    $this->submissionExporter->buildExportOptionsForm($form, $export_form_state, $export_options);

    // Batch.
    $form['batch'] = [
      '#type' => 'details',
      '#title' => $this->t('Batch settings'),
      '#tree' => TRUE,
    ];
    $form['batch']['default_batch_export_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch export size'),
      '#min' => 1,
      '#required' => TRUE,
      '#default_value' => $config->get('batch.default_batch_export_size'),
    ];
    $form['batch']['default_batch_update_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch update size'),
      '#min' => 1,
      '#required' => TRUE,
      '#default_value' => $config->get('batch.default_batch_update_size'),
    ];
    $form['batch']['default_batch_delete_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch delete size'),
      '#min' => 1,
      '#required' => TRUE,
      '#default_value' => $config->get('batch.default_batch_delete_size'),
    ];

    $form['purge_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Automated purging settings'),
      '#tree' => TRUE,
    ];
    $form['purge_settings']['cron_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Amount of submissions to process'),
      '#min' => 1,
      '#default_value' => $config->get('purge_settings.cron_size'),
      '#description' => $this->t('Amount of submissions to purge during single cron run. You may want to lower this number if you are facing memory or timeout issues when purging via cron.'),
    ];

    // Test.
    $form['test'] = [
      '#type' => 'details',
      '#title' => $this->t('Test settings'),
      '#tree' => TRUE,
    ];
    $form['test']['types'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Test data by element type'),
      '#description' => $this->t("Above test data is keyed by FAPI element #type."),
      '#default_value' => $config->get('test.types'),
    ];
    $form['test']['names'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Test data by element name'),
      '#description' => $this->t("Above test data is keyed by full or partial element names. For example, Using 'zip' will populate fields that are named 'zip' and 'zip_code' but not 'zipcode' or 'zipline'."),
      '#default_value' => $config->get('test.names'),
    ];

    // UI.
    $form['ui'] = [
      '#type' => 'details',
      '#title' => $this->t('User interface settings'),
      '#tree' => TRUE,
    ];
    $form['ui']['video_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Video display'),
      '#description' => $this->t('Controls how videos are displayed in inline help and within the global help section.'),
      '#options' => [
        'dialog' => $this->t('Dialog'),
        'link' => $this->t('External link'),
        'hidden' => $this->t('Hidden'),
      ],
      '#default_value' => $config->get('ui.video_display'),
    ];
    $form['ui']['details_save'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Save details open/close state'),
      '#description' => $this->t('If checked, all <a href=":details_href">Details</a> element\'s open/close state will be saved using <a href=":local_storage_href">Local Storage</a>.', [
        ':details_href' => 'http://www.w3schools.com/tags/tag_details.asp',
        ':local_storage_href' => 'http://www.w3schools.com/html/html5_webstorage.asp',
      ]),
      '#return_value' => TRUE,
      '#default_value' => $config->get('ui.details_save'),
    ];
    $form['ui']['dialog_disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable dialogs'),
      '#description' => $this->t('If checked, all modal dialogs (ie popups) will be disabled.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('ui.dialog_disabled'),
    ];
    $form['ui']['offcanvas_disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable off-canvas system tray'),
      '#description' => $this->t('If checked, off-canvas system tray will be disabled.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('ui.offcanvas_disabled'),
      '#access' => $this->moduleHandler->moduleExists('outside_in') && (floatval(\Drupal::VERSION) >= 8.3),
      '#states' => [
        'visible' => [
          ':input[name="ui[dialog_disabled]"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];
    if (!$this->moduleHandler->moduleExists('outside_in') && (floatval(\Drupal::VERSION) >= 8.3)) {
      $form['ui']['offcanvas_message'] = [
        '#type' => 'webform_message',
        '#message_type' => 'info',
        '#message_message' => $this->t('Enable the experimental <a href=":href">System tray module</a> to improve the Webform module\'s user experience.', [':href' => 'https://www.drupal.org/blog/drupal-82-now-with-more-outside-in']),
        '#states' => [
          'visible' => [
            ':input[name="ui[dialog_disabled]"]' => [
              'checked' => FALSE,
            ],
          ],
        ],
        '#weight' => -100,
      ];
    }
    $form['ui']['html_editor_disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable HTML editor'),
      '#description' => $this->t('If checked, all HTML editor will be disabled.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('ui.html_editor_disabled'),
    ];

    // Libraries.
    $form['libraries'] = [
      '#type' => 'details',
      '#title' => $this->t('Libraries settings'),
      '#description' => $this->t('Uncheck the below optional libraries that you do not want to be used by webform elements.') . '</br>' .
        '<em>' . $this->t('Please note, you can also exclude element types that are dependant on specific libraries.') . '</em>',
      '#tree' => TRUE,
    ];
    $libraries_header = [
      'title' => ['data' => $this->t('Title')],
      'description' => ['data' => $this->t('Description/Notes'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
    ];
    $this->libraries = [];
    $libraries_options = [];
    $libraries = $this->librariesManager->getLibraries();
    foreach ($libraries as $library_name => $library) {
      // Only optional libraries can be excluded.
      if (empty($library['optional'])) {
        continue;
      }

      $this->libraries[$library_name] = $library_name;
      $libraries_options[$library_name] = [
        'title' => $library['title'],
        'description' => [
          'data' => [
            'content' => ['#markup' => $library['description'], '#suffix' => '<br/>'],
            'notes' => ['#markup' => '(' . $library['notes'] . ')', '#prefix' => '<em>', '#suffix' => '</em><br/>'],
          ],
        ],
      ];
    }
    $form['libraries']['excluded_libraries'] = [
      '#type' => 'tableselect',
      '#title' => $this->t('Libraries'),
      '#header' => $libraries_header,
      '#js_select' => FALSE,
      '#options' => $libraries_options,
      '#default_value' => array_diff($this->libraries, array_combine($config->get('libraries.excluded_libraries'), $config->get('libraries.excluded_libraries'))),
    ];
    $t_args = [
      ':select2_href' => $libraries['jquery.select2']['homepage_url']->toString(),
      ':chosen_href' => $libraries['jquery.chosen']['homepage_url']->toString(),
    ];
    $form['libraries']['select_message'] = [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t('<a href=":select2_href">Select2</a> and <a href=":chosen_href">Chosen</a> provide very similar functionality, Most websites should only have one of these libraries enabled.', $t_args),
      '#states' => [
        'visible' => [
          ':input[name="libraries[excluded_libraries][jquery.select2]"]' => ['checked' => TRUE],
          ':input[name="libraries[excluded_libraries][jquery.chosen]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['libraries']['cdn'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use CDN'),
      '#description' => $this->t('If checked, all warnings about missing libraries will be disabled.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('libraries.cdn'),
    ];
    $form['libraries']['cdn_message'] = [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t('Note that it is in generally not a good idea to load libraries from a CDN; avoid this if possible. It introduces more points of failure both performance- and security-wise, requires more TCP/IP connections to be set up and these external assets are usually not in the browser cache anyway.'),
      '#states' => [
        'visible' => [
          ':input[name="libraries[cdn]"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /* Settings */

    $settings = $form_state->getValue('page')
      + $form_state->getValue('form')
      + $form_state->getValue('form_behaviors')
      + $form_state->getValue('submission_behaviors')
      + $form_state->getValue('wizard')
      + $form_state->getValue('preview')
      + $form_state->getValue('draft')
      + $form_state->getValue('confirmation')
      + $form_state->getValue('limit');

    // Track if we need to trigger an update of all webform paths
    // because the 'default_page_base_path' changed.
    $update_paths = ($settings['default_page_base_path'] != $this->config('webform.settings')->get('settings.default_page_base_path')) ? TRUE : FALSE;

    /* Libraries */

    // Convert list of included types to excluded types.
    $libraries = $form_state->getValue('libraries');
    $libraries['excluded_libraries'] = array_diff($this->libraries, array_filter($libraries['excluded_libraries']));
    ksort($libraries['excluded_libraries']);
    // Note: Must store a simple array of libraries because library names
    // may contain periods, which is not supported by Drupal's
    // config management.
    $libraries['excluded_libraries'] = array_keys($libraries['excluded_libraries']);

    /* Format */

    $format = $form_state->getValue('format');
    foreach ($format as $element_id => $element_format) {
      $format[$element_id] = array_filter($element_format);
    }
    $format = array_filter($format);

    /* Excluded types */

    // Convert list of included types to excluded types.
    $excluded_types = array_diff($this->elementTypes, array_filter($form_state->getValue('excluded_types')));
    ksort($excluded_types);

    /* Config save */

    $config = $this->config('webform.settings');
    $config->set('settings', $settings);
    $config->set('assets', $form_state->getValue('assets'));
    $config->set('elements', $form_state->getValue('elements') + ['excluded_types' => $excluded_types]);
    $config->set('file', $form_state->getValue('file'));
    $config->set('format', $format);
    $config->set('mail', $form_state->getValue('mail'));
    $config->set('export', $this->submissionExporter->getValuesFromInput($form_state->getValues()));
    $config->set('batch', $form_state->getValue('batch'));
    $config->set('purge_settings', $form_state->getValue('purge_settings'));
    $config->set('test', $form_state->getValue('test'));
    $config->set('ui', $form_state->getValue('ui'));
    $config->set('libraries', $libraries);
    $config->save();

    /* Update paths */

    if ($update_paths) {
      /** @var \Drupal\webform\WebformInterface[] $webforms */
      $webforms = Webform::loadMultiple();
      foreach ($webforms as $webform) {
        $webform->updatePaths();
      }
    }

    // Reset token cache to make 'webform_role' tokens are available.
    \Drupal::token()->resetInfo();

    // Reset libraries cached.
    \Drupal::service('library.discovery')->clearCachedDefinitions();

    parent::submitForm($form, $form_state);
  }

  /**
   * Wrapper for FileItem::validateExtensions.
   */
  public static function validateExtensions($element, FormStateInterface $form_state) {
    if (class_exists('\Drupal\file\Plugin\Field\FieldType\FileItem')) {
      FileItem::validateExtensions($element, $form_state);
    }
  }

  /**
   * Wrapper for FileItem::validateMaxFilesize.
   */
  public static function validateMaxFilesize($element, FormStateInterface $form_state) {
    if (class_exists('\Drupal\file\Plugin\Field\FieldType\FileItem')) {
      FileItem::validateMaxFilesize($element, $form_state);
    }
  }

}
