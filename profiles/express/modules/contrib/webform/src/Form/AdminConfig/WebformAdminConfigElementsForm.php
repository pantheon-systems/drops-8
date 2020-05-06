<?php

namespace Drupal\webform\Form\AdminConfig;

use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\Environment;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\Utility\WebformOptionsHelper;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\WebformLibrariesManagerInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure webform admin settings for elements.
 */
class WebformAdminConfigElementsForm extends WebformAdminConfigBaseForm {

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
   * The webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * The libraries manager.
   *
   * @var \Drupal\webform\WebformLibrariesManagerInterface
   */
  protected $librariesManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_admin_config_elements_form';
  }

  /**
   * Constructs a WebformAdminConfigElementsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager
   *   The webform libraries manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, WebformTokenManagerInterface $token_manager, WebformElementManagerInterface $element_manager, WebformLibrariesManagerInterface $libraries_manager) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
    $this->tokenManager = $token_manager;
    $this->elementManager = $element_manager;
    $this->librariesManager = $libraries_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('webform.token_manager'),
      $container->get('plugin.manager.webform.element'),
      $container->get('webform.libraries_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('webform.settings');

    // Element: Settings.
    $form['element'] = [
      '#type' => 'details',
      '#title' => $this->t('Element general settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['element']['machine_name_pattern'] = [
      '#type' => 'select',
      '#title' => $this->t('Element key pattern'),
      '#description' => $this->t('The element key pattern is used to limit the format of element keys.') . '<br/><br/>' .
        $this->t('Please note: Automatically generated element keys are lowercased letters, numbers, and underscores'),
      '#options' => [
        'a-z0-9_' => $this->t('Lowercase letters, numbers, and underscores. (i.e. element_key)'),
        'a-zA-Z0-9_' => $this->t('Letters, numbers, and underscores. (i.e. element_KEY)'),
        'a-z0-9_-' => $this->t('Lowercase letters, numbers, underscores, and dashes. (i.e. element-key)'),
        'a-zA-Z0-9_-' => $this->t('Letters, numbers, underscores, and dashes. (i.e. element-KEY)'),
      ],
      '#required' => TRUE,
      '#default_value' => $config->get('element.machine_name_pattern'),
    ];
    $form['element']['empty_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Empty element message/placeholder'),
      '#description' => $this->t('Text that will be shown when empty elements are included in submission previews and/or emails'),
      '#default_value' => $config->get('element.empty_message'),
    ];
    $form['element']['allowed_tags'] = [
      '#type' => 'webform_radios_other',
      '#title' => $this->t('Allowed tags'),
      '#options' => [
        'admin' => $this->t('Admin tags Excludes: script, iframe, etc…'),
        'html' => $this->t('HTML tags: Includes only @html_tags.', ['@html_tags' => WebformArrayHelper::toString(Xss::getHtmlTagList())]),
      ],
      '#other__option_label' => $this->t('Custom tags'),
      '#other__placeholder' => $this->t('Enter multiple tags delimited using spaces'),
      '#other__default_value' => implode(' ', Xss::getAdminTagList()),
      '#other__maxlength' => 1000,
      '#required' => TRUE,
      '#description' => $this->t('Allowed tags are applied to any element property that may contain HTML markup. Element properties which can contain HTML markup include #title, #description, #field_prefix, and #field_suffix.'),
      '#default_value' => $config->get('element.allowed_tags'),
    ];
    $form['element']['wrapper_classes'] = [
      '#type' => 'webform_codemirror',
      '#title' => $this->t('Wrapper CSS classes'),
      '#description' => $this->t('A list of classes that will be provided in the "Wrapper CSS classes" dropdown. Enter one or more classes on each line. These styles should be available in your theme\'s CSS file.'),
      '#required' => TRUE,
      '#default_value' => $config->get('element.wrapper_classes'),
    ];
    $form['element']['classes'] = [
      '#type' => 'webform_codemirror',
      '#title' => $this->t('Element CSS classes'),
      '#description' => $this->t('A list of classes that will be provided in the "Element CSS classes" dropdown. Enter one or more classes on each line. These styles should be available in your theme\'s CSS file.'),
      '#required' => TRUE,
      '#default_value' => $config->get('element.classes'),
    ];
    $form['element']['horizontal_rule_classes'] = [
      '#type' => 'webform_codemirror',
      '#title' => $this->t('Horizontal rule CSS classes'),
      '#description' => $this->t('A list of classes that will be provided in the "Horizontal rule  CSS classes" dropdown. Enter one or more classes on each line. These styles should be available in your theme\'s CSS file.'),
      '#required' => TRUE,
      '#default_value' => $config->get('element.horizontal_rule_classes'),
    ];
    // Element: Description/Help.
    $form['element']['default_description_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Default description display'),
      '#empty_option' => $this->t('- Default -'),
      '#options' => [
        'before' => $this->t('Before'),
        'after' => $this->t('After'),
        'invisible' => $this->t('Invisible'),
        'tooltip' => $this->t('Tooltip'),
      ],
      '#description' => $this->t('Determines the default placement of the description for all webform elements.'),
      '#default_value' => $config->get('element.default_description_display'),
    ];
    $form['element']['default_more_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default more label'),
      '#description' => $this->t('The (read) more label used to hide/show more information about an element.'),
      '#required' => 'required',
      '#default_value' => $config->get('element.default_more_title'),
    ];
    $form['element']['default_section_title_tag'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Default section title tag'),
      '#options' => [
        'h1' => $this->t('Header 1 (h1)'),
        'h2' => $this->t('Header 2 (h2)'),
        'h3' => $this->t('Header 3 (h3)'),
        'h4' => $this->t('Header 4 (h4)'),
        'h5' => $this->t('Header 5 (h5)'),
        'h6' => $this->t('Header 6 (h6)'),
        'label' => $this->t('Label (label)'),
      ],
      '#required' => 'required',
      '#default_value' => $config->get('element.default_section_title_tag'),
    ];

    // Element: HTML Editor.
    $form['html_editor'] = [
      '#type' => 'details',
      '#title' => $this->t('HTML editor settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['html_editor']['tidy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Tidy HTML markup'),
      '#description' => $this->t('If checked, &lt;p&gt; tags, which can add top and bottom margins, will be removed from all single line HTML markup.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('html_editor.tidy'),
    ];
    $form['html_editor']['disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable HTML editor'),
      '#description' => $this->t('If checked, all HTML editors will be disabled.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('html_editor.disabled'),
    ];
    $format_options = [];
    if ($this->moduleHandler->moduleExists('filter')) {
      $filters = filter_formats();
      foreach ($filters as $filter) {
        $format_options[$filter->id()] = $filter->label();
      }
    }
    $form['html_editor']['format_container'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="html_editor[disabled]"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['html_editor']['format_container']['element_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Element text format'),
      '#description' => $this->t('Leave blank to use the custom and recommended Webform specific HTML editor.'),
      '#empty_option' => $this->t('- None -'),
      '#options' => $format_options,
      '#default_value' => $config->get('html_editor.element_format'),
      '#parents' => ['html_editor', 'element_format'],
    ];
    $form['html_editor']['format_container']['mail_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Mail text format'),
      '#description' => $this->t('Leave blank to use the custom and recommended Webform specific HTML editor.'),
      '#empty_option' => $this->t('- None -'),
      '#options' => $format_options,
      '#default_value' => $config->get('html_editor.mail_format'),
      '#parents' => ['html_editor', 'mail_format'],
      '#states' => [
        'visible' => [
          ':input[name="html_editor[disabled]"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['html_editor']['format_container']['make_unused_managed_files_temporary'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Unused html editor files should be marked temporary'),
      '#description' => $this->t('Drupal core does not automatically delete unused files because unused files could reused.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('html_editor.make_unused_managed_files_temporary'),
      '#parents' => ['html_editor', 'make_unused_managed_files_temporary'],
      '#states' => [
        'visible' => [
          [':input[name="html_editor[element_format]"]' => ['!value' => '']],
          'or',
          [':input[name="html_editor[mail_format]"]' => ['!value' => '']],
        ],
      ],
    ];
    $form['html_editor']['format_container']['warning_message'] = [
      '#type' => 'webform_message',
      '#message_message' => $this->t('Files uploaded via the CKEditor file dialog to webform elements, settings, and configuration will not be exportable.') . '<br/>' .
        '<strong>' . $this->t('All files must be uploaded to your production environment and then copied to development and local environment.') . '</strong>',
      '#message_type' => 'warning',
      '#states' => [
        'visible' => [
          [':input[name="html_editor[element_format]"]' => ['!value' => '']],
          'or',
          [':input[name="html_editor[mail_format]"]' => ['!value' => '']],
        ],
      ],
      '#message_close' => TRUE,
      '#message_storage' => WebformMessage::STORAGE_SESSION,
    ];
    if (!$this->moduleHandler->moduleExists('imce')) {
      $form['html_editor']['format_container']['help_message'] = [
        '#type' => 'webform_message',
        '#message_message' => $this->t('It is recommended to use the <a href=":href">IMCE module</a> to manage webform elements, settings, and configuration files.', [':href' => 'https://www.drupal.org/project/imce']),
        '#message_type' => 'info',
        '#states' => [
          'visible' => [
            [':input[name="html_editor[element_format]"]' => ['!value' => '']],
            'or',
            [':input[name="html_editor[mail_format]"]' => ['!value' => '']],
          ],
        ],
        '#message_close' => TRUE,
        '#message_storage' => WebformMessage::STORAGE_SESSION,
      ];
    }

    // Element: Location.
    $form['location'] = [
      '#type' => 'details',
      '#title' => $this->t('Location settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#access' => $this->librariesManager->isIncluded('jquery.geocomplete') || $this->librariesManager->isIncluded('algolia.places'),
    ];
    $form['location']['default_algolia_places_app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Algolia application id'),
      '#description' => $this->t('Algolia requires users to use a valid application id and API key for more than 1,000 requests per day. By <a href="https://www.algolia.com/users/sign_up/places">signing up</a>, you can create a free Places app and access your API keys.'),
      '#default_value' => $config->get('element.default_algolia_places_app_id'),
      '#access' => $this->librariesManager->isIncluded('algolia.places'),
    ];
    $form['location']['default_algolia_places_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Algolia API key'),
      '#default_value' => $config->get('element.default_algolia_places_api_key'),
      '#access' => $this->librariesManager->isIncluded('algolia.places'),
    ];

    // Element: Select.
    $form['select'] = [
      '#type' => 'details',
      '#title' => $this->t('Select settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['select']['default_empty_option'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Default empty option'),
      '#description' => $this->t('If checked, the first default option for a select menu will always be displayed.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('element.default_empty_option'),
    ];
    $form['select']['default_empty_option_required'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default empty option required'),
      '#description' => $this->t('The label to show for the first default option for a required select menus.') . '<br /><br />' .
        $this->t('Defaults to: %value', ['%value' => $this->t('- Select -')]),
      '#default_value' => $config->get('element.default_empty_option_required'),
    ];
    $form['select']['default_empty_option_optional'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default empty option optional'),
      '#description' => $this->t('The label to show for the first default option for an optional select menus.') . '<br /><br />' .
        $this->t('Defaults to: %value', ['%value' => $this->t('- None -')]),
      '#default_value' => $config->get('element.default_empty_option_optional'),
    ];

    // Element: File.
    $form['file'] = [
      '#type' => 'details',
      '#title' => $this->t('File upload settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['file']['make_unused_managed_files_temporary'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Unused webform submission files should be marked temporary'),
      '#description' => $this->t('Drupal core does not automatically delete unused files because unused files could reused. For webform submissions it is recommended that unused files are deleted.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('file.make_unused_managed_files_temporary'),
    ];
    $form['file']['delete_temporary_managed_files'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Immediately delete temporary managed files'),
      '#description' => $this->t('Drupal core does not immediately delete temporary file. For webform submissions it is recommended that temporary files are immediately deleted.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('file.delete_temporary_managed_files'),
    ];
    $form['file']['file_public'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow files to be uploaded to public file system'),
      '#description' => $this->t('Allowing public file uploads is dangerous for webforms that are available to anonymous and/or untrusted users.') . ' ' .
        $this->t('For more information see: <a href="https://www.drupal.org/psa-2016-003">DRUPAL-PSA-2016-003</a>'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('file.file_public'),
    ];
    $form['file']['file_private_redirect'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Redirect anonymous users to login when attempting to access private file uploads'),
      '#description' => $this->t('If checked, anonymous users will be redirected to the user login page when attempting to access private file uploads.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('file.file_private_redirect'),
    ];
    $form['file']['file_private_redirect_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Login message when access denied to private file uploads'),
      '#required' => TRUE,
      '#default_value' => $config->get('file.file_private_redirect_message'),
      '#states' => [
        'visible' => [
          ':input[name="file[file_private_redirect]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['file']['default_max_filesize'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default maximum file upload size'),
      '#description' => $this->t('Enter a value like "512" (bytes), "80 KB" (kilobytes) or "50 MB" (megabytes) in order to restrict the allowed file size. If left empty the file sizes will be limited only by PHP\'s maximum post and file upload sizes.')
        . '<br /><br />'
        . $this->t('Current limit: %limit', ['%limit' => format_size(Environment::getUploadMaxSize())]),
      '#element_validate' => [[get_class($this), 'validateMaxFilesize']],
      '#size' => 10,
      '#default_value' => $config->get('file.default_max_filesize'),
    ];
    $form['file']['default_form_file_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default file upload limit per form'),
      '#description' => $this->t('Enter a value like "512" (bytes), "80 KB" (kilobytes) or "50 MB" (megabytes) in order to set file upload limit.'),
      '#element_validate' => [[get_class($this), 'validateMaxFilesize']],
      '#size' => 10,
      '#default_value' => $config->get('settings.default_form_file_limit'),
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
    $form['file']['token_tree_link'] = $this->tokenManager->buildTreeElement();

    // Element: (Excluded) Types.
    $form['types'] = [
      '#type' => 'details',
      '#title' => $this->t('Element types'),
      '#description' => $this->t('Select available element types'),
    ];
    $form['types']['excluded_elements'] = $this->buildExcludedPlugins(
      $this->elementManager,
      $config->get('element.excluded_elements')
    );
    $form['types']['excluded_elements']['#header']['title']['width'] = '25%';
    $form['types']['excluded_elements']['#header']['id']['width'] = '25%';
    $form['types']['excluded_elements']['#header']['description']['width'] = '50%';
    // Add warning to all password elements.
    foreach ($form['types']['excluded_elements']['#options'] as $element_type => &$excluded_element_option) {
      if (strpos($element_type, 'password') !== FALSE) {
        $excluded_element_option['description']['data']['message'] = [
          '#type' => 'webform_message',
          '#message_type' => 'warning',
          '#message_message' => $this->t('Webform submissions store passwords as plain text.') . ' ' .
            $this->t('Any webform that includes this element should enable <a href=":href">encryption</a>.', [':href' => 'https://www.drupal.org/project/webform_encrypt']),
          '#attributes' => ['class' => ['js-form-wrapper']],
          '#states' => [
            'visible' => [
              ':input[name="excluded_elements[' . $element_type . ']"]' => ['checked' => TRUE],
            ],
          ],
        ];
      }
    }

    // Element: Format.
    $form['format'] = [
      '#type' => 'details',
      '#title' => $this->t('Element formats'),
      '#description' => $this->t('Select default element item and items format.'),
    ];
    $element_plugins = $this->elementManager->getInstances();
    $rows = [];
    foreach ($element_plugins as $element_id => $element_plugin) {
      $element_plugin_definition = $element_plugin->getPluginDefinition();
      $element_plugin_label = $element_plugin_definition['label'];
      $element_plugin_states = [
        'disabled' => [
          ':input[name="excluded_elements[' . $element_id . ']"]' => ['checked' => FALSE],
        ],
      ];

      $row = [];

      // Title.
      $row['title'] = ['#markup' => $element_plugin_label];

      // ID.
      $row['id'] = ['#markup' => $element_id];

      // Item format.
      $item_formats = WebformOptionsHelper::appendValueToText($element_plugin->getItemFormats());
      $item_default_format = $element_plugin->getItemDefaultFormat();
      $item_default_format_label = (isset($item_formats[$item_default_format])) ? $item_formats[$item_default_format] : $item_default_format;
      $row['item'] = [
        '#type' => 'select',
        '#title' => $this->t('Item format'),
        '#title_display' => 'invisible',
        '#field_suffix' => [
          '#type' => 'webform_help',
          '#help' => $this->t('Defaults to: %value', ['%value' => $item_default_format_label]),
        ],
        '#empty_option' => $this->t('- Default -'),
        '#options' => $item_formats,
        '#default_value' => $config->get("format.$element_id"),
        '#parents' => ['format', $element_id, 'item'],
        '#states' => $element_plugin_states,
      ];

      // Items format.
      if ($element_plugin->supportsMultipleValues()) {
        $items_formats = WebformOptionsHelper::appendValueToText($element_plugin->getItemsFormats());
        $items_default_format = $element_plugin->getItemsDefaultFormat();
        $items_default_format_label = (isset($item_formats[$items_default_format])) ? $items_formats[$items_default_format] : $items_default_format;
        $row['items'] = [
          '#type' => 'select',
          '#title' => $this->t('Items format'),
          '#title_display' => 'invisible',
          '#field_suffix' => [
            '#help_title' => $element_plugin_label,
            '#help' => $this->t('Defaults to: %value', ['%value' => $items_default_format_label]),
          ],
          '#empty_option' => $this->t('- Default -'),
          '#options' => $items_formats,
          '#default_value' => $config->get("format.$element_id"),
          '#parents' => ['format', $element_id, 'items'],
          '#states' => $element_plugin_states,
        ];
      }
      else {
        $row['items'] = ['#markup' => ''];
      }

      $rows[$element_id] = $row;
    }
    $form['format']['elements'] = [
      '#type' => 'table',
      '#header' => [
        'title' => ['data' => $this->t('Title'), 'width' => '25%'],
        'id' => ['data' => $this->t('Name'), 'class' => [RESPONSIVE_PRIORITY_LOW], 'width' => '25%'],
        'item' => ['data' => $this->t('Item format'), 'width' => '25%'],
        'items' => ['data' => $this->t('Items format'), 'width' => '25%'],
      ],
      '#sticky' => TRUE,
    ] + $rows;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Format.
    $format = $form_state->getValue('format');
    foreach ($format as $element_id => $element_format) {
      $format[$element_id] = array_filter($element_format);
    }
    $format = array_filter($format);

    // Excluded elements.
    $excluded_elements = $this->convertIncludedToExcludedPluginIds($this->elementManager, $form_state->getValue('excluded_elements'));

    // Update config and submit form.
    $config = $this->config('webform.settings');

    $config->set('element', $form_state->getValue('element') +
      $form_state->getValue('location') +
      $form_state->getValue('select') +
      ['excluded_elements' => $excluded_elements]
    );

    $config->set('html_editor', $form_state->getValue('html_editor'));

    $file = $form_state->getValue('file');
    $config->set('settings.default_form_file_limit', $file['default_form_file_limit']);
    unset($file['default_form_file_limit']);
    $config->set('file', $file);

    $config->set('format', $format);

    parent::submitForm($form, $form_state);

    // Reset libraries cached.
    // @see webform_library_info_build()
    \Drupal::service('library.discovery')->clearCachedDefinitions();
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
    // Issue #2359675: File field's Maximum upload size always passes validation.
    // if (class_exists('\Drupal\file\Plugin\Field\FieldType\FileItem')) {
    //   FileItem::validateMaxFilesize($element, $form_state);
    // }
    // @see \Drupal\file\Plugin\Field\FieldType\FileItem::validateMaxFilesize
    if (!empty($element['#value']) && !Bytes::toInt($element['#value'])) {
      $form_state->setError($element, t('The "@name" option must contain a valid value. You may either leave the text field empty or enter a string like "512" (bytes), "80 KB" (kilobytes) or "50 MB" (megabytes).', ['@name' => $element['#title']]));
    }
  }

}
