<?php

namespace Drupal\webform\Form\AdminConfig;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\WebformLibrariesManagerInterface;
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
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager
   *   The webform libraries manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, WebformElementManagerInterface $element_manager, WebformLibrariesManagerInterface $libraries_manager) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
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
      '#title' => $this->t('Element settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
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
        'admin' => $this->t('Admin tags Excludes: script, iframe, etc...'),
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
      '#options' => [
        '' => '',
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
      '#description' => $this->t('The (read) more label used hide/show more information about an element.'),
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

    // Element: Checkbox/Radio.
    $form['checkbox'] = [
      '#type' => 'details',
      '#title' => $this->t('Checkbox/radio settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['checkbox']['default_icheck'] = [
      '#type' => 'select',
      '#title' => $this->t('Enhance checkboxes/radio buttons using iCheck'),
      '#description' => $this->t('If set, all checkboxes/radio buttons with be enhanced using jQuery <a href=":href">iCheck</a> boxes.', [':href' => 'http://icheck.fronteed.com/']),
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
        (string) $this->t('Line') => [
          'line' => $this->t('Line: Black'),
          'line-grey' => $this->t('Line: Grey'),
          'line-yellow' => $this->t('Line: Yellow'),
          'line-orange' => $this->t('Line: Orange'),
          'line-red' => $this->t('Line: Red'),
          'line-pink' => $this->t('Line: Pink'),
          'line-purple' => $this->t('Line: Purple'),
          'line-blue' => $this->t('Line: Blue'),
          'line-green' => $this->t('Line: Green'),
          'line-aero' => $this->t('Line: Aero'),
        ],
      ],
      '#default_value' => $config->get('element.default_icheck'),
      '#access' => $this->librariesManager->isIncluded('jquery.icheck'),
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
    $format_options = ['' => ''];
    if ($this->moduleHandler->moduleExists('filter')) {
      $filters = filter_formats();
      foreach ($filters as $filter) {
        $format_options[$filter->id()] = $filter->label();
      }
    }
    $form['html_editor']['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Text format'),
      '#description' => $this->t('Leave blank to use the custom and recommended Webform specific HTML editor.'),
      '#options' => $format_options,
      '#default_value' => $config->get('html_editor.format'),
      '#states' => [
        'visible' => [
          ':input[name="html_editor[disabled]"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $t_args = [
      ':dialog_href' => Url::fromRoute('<current>', [], ['fragment' => 'edit-ui'])->toString(),
      ':modules_href' => Url::fromRoute('system.modules_list', [], ['fragment' => 'edit-modules-core-experimental'])->toString(),
    ];
    $form['html_editor']['message'] = [
      '#type' => 'webform_message',
      '#message_message' => $this->t('Text formats that open CKEditor image and/or link dialogs will not work properly.') . '<br />' .
        $this->t('You may need to <a href=":dialog_href">disable dialogs</a> or enable the experimental <a href=":modules_href">Settings Tray</a> module.', $t_args) . '<br />' .
        $this->t('For more information see: <a href="https://www.drupal.org/node/2741877">Issue #2741877: Nested modals don\'t work</a>'),
      '#message_type' => 'warning',
      '#states' => [
        'visible' => [
          ':input[name="html_editor[disabled]"]' => ['checked' => FALSE],
          ':input[name="html_editor[format]"]' => ['!value' => ''],
        ],
      ],
    ];

    // Element: Location.
    $form['location'] = [
      '#type' => 'details',
      '#title' => $this->t('Location settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['location']['default_google_maps_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API key'),
      '#description' => $this->t('Google requires users to use a valid API key. Using the <a href="https://console.developers.google.com/apis">Google API Manager</a>, you can enable the <em>Google Maps JavaScript API</em>. That will create (or reuse) a <em>Browser key</em> which you can paste here.'),
      '#default_value' => $config->get('element.default_google_maps_api_key'),
      '#access' => $this->librariesManager->isIncluded('jquery.geocomplete'),
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
      $item_formats = $element_plugin->getItemFormats();
      foreach ($item_formats as $format_name => $format_label) {
        if (is_array($format_label)) {
          // Support optgroup.
          // @see \Drupal\webform\Plugin\WebformElement\WebformImageFile::getItemFormats.
          foreach ($format_label as $format_label_value => $format_label_text) {
            $item_formats[$format_name][$format_label_value] = new FormattableMarkup('@label (@name)', ['@label' => $format_label_text, '@name' => $format_label_value]);
          }
        }
        else {
          $item_formats[$format_name] = new FormattableMarkup('@label (@name)', ['@label' => $format_label, '@name' => $format_name]);
        }
      }
      $item_formats = ['' => '<' . $this->t('Default') . '>'] + $item_formats;
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
        '#options' => $item_formats,
        '#default_value' => $config->get("format.$element_id"),
        '#parents' => ['format', $element_id, 'item'],
        '#states' => $element_plugin_states,
      ];

      // Items format.
      if ($element_plugin->supportsMultipleValues()) {
        $items_formats = $element_plugin->getItemsFormats();
        foreach ($items_formats as $format_name => $format_label) {
          $items_formats[$format_name] = new FormattableMarkup('@label (@name)', ['@label' => $format_label, '@name' => $format_name]);
        }
        $items_formats = ['' => '<' . $this->t('Default') . '>'] + $items_formats;
        $items_default_format = $element_plugin->getItemsDefaultFormat();
        $items_default_format_label = (isset($item_formats[$items_default_format])) ? $items_formats[$items_default_format] : $items_default_format;
        $row['items'] = [
          '#type' => 'select',
          '#title' => $this->t('Items format'),
          '#title_display' => 'invisible',
          '#field_suffix' => [
            '#type' => 'webform_help',
            '#help' => $this->t('Defaults to: %value', ['%value' => $items_default_format_label]),
          ],
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

    $config = $this->config('webform.settings');
    $config->set('element', $form_state->getValue('element') +
      $form_state->getValue('checkbox') +
      $form_state->getValue('location') +
      $form_state->getValue('select') +
      ['excluded_elements' => $excluded_elements]
    );
    $config->set('html_editor', $form_state->getValue('html_editor'));
    $config->set('file', $form_state->getValue('file'));
    $config->set('format', $format);
    $config->save();

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
