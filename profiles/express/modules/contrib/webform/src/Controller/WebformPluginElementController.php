<?php

namespace Drupal\webform\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Url;
use Drupal\webform\Utility\WebformReflectionHelper;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for all webform elements.
 */
class WebformPluginElementController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * A element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

  /**
   * A webform element plugin manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Constructs a WebformPluginElementController object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info
   *   A element info plugin manager.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   A webform element plugin manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, ElementInfoManagerInterface $element_info, WebformElementManagerInterface $element_manager) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->elementInfo = $element_info;
    $this->elementManager = $element_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('plugin.manager.element_info'),
      $container->get('plugin.manager.webform.element')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function index() {
    $webform_form_element_rows = [];
    $element_rows = [];

    $excluded_elements = $this->config('webform.settings')->get('element.excluded_elements');

    $default_properties = [
      // Element settings.
      'title',
      'description',
      'default_value',
      // Form display.
      'title_display',
      'description_display',
      'field_prefix',
      'field_suffix',
      // Form validation.
      'required',
      'required_error',
      'unique',
      // Submission display.
      'format',
      // Attributes.
      'wrapper_attributes',
      'attributes',
      // Administration.
      'admin_title',
      'private',
      // Flexbox.
      'flex',
      // Conditional logic.
      'states',
      // Element access.
      'access_create_roles',
      'access_create_users',
      'access_update_roles',
      'access_update_users',
      'access_view_roles',
      'access_view_users',
    ];;
    $default_properties = array_combine($default_properties, $default_properties);

    // Test element is only enabled if the Webform Devel and UI module are
    // enabled.
    $test_element_enabled = ($this->moduleHandler->moduleExists('webform_devel') && $this->moduleHandler->moduleExists('webform_ui')) ? TRUE : FALSE;

    // Define a default element used to get default properties.
    $element = ['#type' => 'element'];

    $element_plugin_definitions = $this->elementInfo->getDefinitions();
    foreach ($element_plugin_definitions as $element_plugin_id => $element_plugin_definition) {
      if ($this->elementManager->hasDefinition($element_plugin_id)) {

        /** @var \Drupal\webform\Plugin\WebformElementInterface $webform_element */
        $webform_element = $this->elementManager->createInstance($element_plugin_id);
        $webform_element_plugin_definition = $this->elementManager->getDefinition($element_plugin_id);
        $webform_element_info = $webform_element->getInfo();

        // Title.
        if ($test_element_enabled) {
          $title = [
            'data' => [
              '#type' => 'link',
              '#title' => $element_plugin_id,
              '#url' => new Url('webform.element_plugins.test', ['type' => $element_plugin_id]),
              '#attributes' => ['class' => ['webform-form-filter-text-source']],
            ],
          ];
        }
        else {
          $title = new FormattableMarkup('<div class="webform-form-filter-text-source">@id</div>', ['@id' => $element_plugin_id]);
        }

        // Description.
        $description = new FormattableMarkup('<strong>@label</strong><br />@description', ['@label' => $webform_element->getPluginLabel(), '@description' => $webform_element->getPluginDescription()]);

        // Parent classes.
        $parent_classes = WebformReflectionHelper::getParentClasses($webform_element, 'WebformElementBase');

        // Formats.
        $default_format = $webform_element->getItemDefaultFormat();
        $format_names = array_keys($webform_element->getItemFormats());
        $formats = array_combine($format_names, $format_names);
        if (isset($formats[$default_format])) {
          $formats[$default_format] = '<b>' . $formats[$default_format] . '</b>';
        }

        // Related types.
        $related_types = $webform_element->getRelatedTypes($element);

        // Dependencies.
        $dependencies = $webform_element_plugin_definition['dependencies'];

        // Webform element info.
        $webform_info_definitions = [
          'excluded' => isset($excluded_elements[$element_plugin_id]),
          'input' => $webform_element->isInput($element),
          'container' => $webform_element->isContainer($element),
          'root' => $webform_element->isRoot(),
          'hidden' => $webform_element->isHidden(),
          'multiple' => $webform_element->supportsMultipleValues(),
          'multiline' => $webform_element->isMultiline($element),
          'default_key' => $webform_element_plugin_definition['default_key'],
          'states_wrapper' => $webform_element_plugin_definition['states_wrapper'],
        ];
        $webform_info = [];
        foreach ($webform_info_definitions as $key => $value) {
          $webform_info[] = '<b>' . $key . '</b>: ' . ($value ? $this->t('Yes') : $this->t('No'));
        }

        // Wlement info.
        $element_info_definitions = [
          'input' => (empty($webform_element_info['#input'])) ? $this->t('No') : $this->t('Yes'),
          'theme' => (isset($webform_element_info['#theme'])) ? $webform_element_info['#theme'] : 'N/A',
          'theme_wrappers' => (isset($webform_element_info['#theme_wrappers'])) ? implode('; ', $webform_element_info['#theme_wrappers']) : 'N/A',
        ];
        $element_info = [];
        foreach ($element_info_definitions as $key => $value) {
          $element_info[] = '<b>' . $key . '</b>: ' . $value;
        }

        // Properties.
        $properties = [];
        $element_default_properties = array_keys($webform_element->getDefaultProperties());
        foreach ($element_default_properties as $key => $value) {
          if (!isset($default_properties[$value])) {
            $properties[$key] = '<b>#' . $value . '</b>';
            unset($element_default_properties[$key]);
          }
          else {
            $element_default_properties[$key] = '#' . $value;
          }
        }
        $properties += $element_default_properties;
        if (count($properties) >= 20) {
          $properties = array_slice($properties, 0, 20) + ['...' => '...'];
        }

        // Operations.
        $operations = [];
        if ($test_element_enabled) {
          $operations['test'] = [
            'title' => $this->t('Test'),
            'url' => new Url('webform.element_plugins.test', ['type' => $element_plugin_id]),
          ];
        }
        if ($api_url = $webform_element->getPluginApiUrl()) {
          $operations['documentation'] = [
            'title' => $this->t('API Docs'),
            'url' => $api_url,
          ];
        }

        $webform_form_element_rows[$element_plugin_id] = [
          'data' => [
            $title,
            $description,
            ['data' => ['#markup' => implode('<br /> → ', $parent_classes)], 'nowrap' => 'nowrap'],
            ['data' => ['#markup' => implode('<br />', $webform_info)], 'nowrap' => 'nowrap'],
            ['data' => ['#markup' => implode('<br />', $element_info)], 'nowrap' => 'nowrap'],
            ['data' => ['#markup' => implode('<br />', $properties)]],
            $formats ? ['data' => ['#markup' => '• ' . implode('<br />• ', $formats)], 'nowrap' => 'nowrap'] : '',
            $related_types ? ['data' => ['#markup' => '• ' . implode('<br />• ', $related_types)], 'nowrap' => 'nowrap'] : '<' . $this->t('none') . '>',
            $dependencies ? ['data' => ['#markup' => '• ' . implode('<br />• ', $dependencies)], 'nowrap' => 'nowrap'] : '',
            $element_plugin_definition['provider'],
            $webform_element_plugin_definition['provider'],
            $operations ? ['data' => [
              '#type' => 'operations',
              '#links' => $operations,
              '#prefix' => '<div class="webform-dropbutton">',
              '#suffix' => '</div>',
            ]] : '',
          ],
        ];
        if (isset($excluded_elements[$element_plugin_id])) {
          $webform_form_element_rows[$element_plugin_id]['class'] = ['color-warning'];
        }
      }
      else {
        $element_rows[$element_plugin_id] = [
          $element_plugin_id,
          $element_plugin_definition['provider'],
        ];
      }
    }

    $build = [];

    $build['filter'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Filter by element name'),
      '#attributes' => [
        'class' => ['webform-form-filter-text'],
        'data-element' => '.webform-element-plugin',
        'title' => $this->t('Enter a part of the element type to filter by.'),
        'autofocus' => 'autofocus',
      ],
    ];

    // Settings
    $build['settings'] = [
      '#type' => 'link',
      '#title' => $this->t('Edit configuration'),
      '#url' => Url::fromRoute('webform.config.elements'),
      '#attributes' => ['class' => ['button', 'button--small'], 'style' => 'float: right'],
    ];

    // Display info.
    $build['info'] = [
      '#markup' => $this->t('@total exporters', ['@total' => count($webform_form_element_rows)]),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    
    ksort($webform_form_element_rows);
    $build['webform_elements'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Label/Description'),
        $this->t('Class hierarchy'),
        $this->t('Webform info'),
        $this->t('Element info'),
        $this->t('Properties'),
        $this->t('Formats'),
        $this->t('Related'),
        $this->t('Dependencies'),
        $this->t('Provided by'),
        $this->t('Integrated by'),
        $this->t('Operations'),
      ],
      '#rows' => $webform_form_element_rows,
      '#attributes' => [
        'class' => ['webform-element-plugin'],
      ],
    ];

    ksort($element_rows);
    $build['elements'] = [
      '#type' => 'details',
      '#title' => $this->t('Additional elements'),
      '#description' => $this->t('Below are elements that are available but do not have a Webform Element integration plugin.'),
      'table' => [
        '#type' => 'table',
        '#header' => [
          $this->t('Name'),
          $this->t('Provided by'),
        ],
        '#rows' => $element_rows,
        '#sticky' => TRUE,
      ],
    ];

    $all_translatable_properties = $this->elementManager->getTranslatableProperties();
    $all_properties = $this->elementManager->getAllProperties();
    foreach ($all_translatable_properties as $key => $value) {
      $all_translatable_properties[$key] = [
        '#markup' => $value,
        '#prefix' => '<strong>',
        '#suffix' => '</strong>',
        '#weight' => -10,
      ];
    }
    foreach ($all_properties as $key => $value) {
      // Remove all composite properties.
      if (strpos($key, '__')) {
        unset($all_properties[$key]);
      }
    }
    $build['properties'] = [
      '#type' => 'details',
      '#title' => $this->t('Element properties'),
      '#description' => $this->t('Below are all available element properties with translatable properties in <strong>bold</strong>.'),
      'list' => [
        '#theme' => 'item_list',
        '#items' => $all_translatable_properties + $all_properties,
      ],
    ];

    $build['#attached']['library'][] = 'webform/webform.admin';
    $build['#attached']['library'][] = 'webform/webform.form';

    return $build;
  }

  /**
   * Get a class's name without its namespace.
   *
   * @param string $class
   *   A class.
   *
   * @return string
   *   The class's name without its namespace.
   */
  protected function getClassName($class) {
    $parts = preg_split('#\\\\#', $class);
    return end($parts);
  }

}
