<?php

namespace Drupal\webform\Plugin;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\WebformSubmissionForm;

/**
 * Provides a plugin manager for webform element plugins.
 *
 * @see hook_webform_element_info_alter()
 * @see \Drupal\webform\Annotation\WebformElement
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see plugin_api
 */
class WebformElementManager extends DefaultPluginManager implements FallbackPluginManagerInterface, WebformElementManagerInterface {

  use CategorizingPluginManagerTrait;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * A element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

  /**
   * List of already instantiated webform element plugins.
   *
   * @var array
   */
  protected $instances = [];

  /**
   * Constructs a WebformElementManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info
   *   The element info manager.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler, ConfigFactoryInterface $config_factory, ElementInfoManagerInterface $element_info) {
    parent::__construct('Plugin/WebformElement', $namespaces, $module_handler, 'Drupal\webform\Plugin\WebformElementInterface', 'Drupal\webform\Annotation\WebformElement');
    $this->configFactory = $config_factory;
    $this->elementInfo = $element_info;
    $this->themeHandler = $theme_handler;

    $this->alterInfo('webform_element_info');
    $this->setCacheBackend($cache_backend, 'webform_element_plugins');
  }

  /**
   * {@inheritdoc}
   */
  protected function alterDefinitions(&$definitions) {
    // Prevents Fatal error: Class 'Drupal\bootstrap\Bootstrap' during install
    // w/ Bootstrap theme and webform.
    $this->themeHandler->reset();

    // Unset elements that are missing target element or dependencies.
    foreach ($definitions as $element_key => $element_definition) {
      // Check that the webform element's target element info exists.
      if (!$this->elementInfo->getInfo($element_key)) {
        unset($definitions[$element_key]);
        continue;
      }

      // Check element's (module) dependencies exist.
      foreach ($element_definition['dependencies'] as $dependency) {
        if (!$this->moduleHandler->moduleExists($dependency)) {
          unset($definitions[$element_key]);
          continue;
        }
      }
    }

    parent::alterDefinitions($definitions);
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'webform_element';
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    // If configuration is empty create a single reusable instance for each
    // Webform element plugin.
    if (empty($configuration)) {
      if (!isset($this->instances[$plugin_id])) {
        $this->instances[$plugin_id] = parent::createInstance($plugin_id, $configuration);
      }
      return $this->instances[$plugin_id];
    }
    else {
      return parent::createInstance($plugin_id, $configuration);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getInstances() {
    $plugin_definitions = $this->getDefinitions();
    $plugin_definitions = $this->getSortedDefinitions($plugin_definitions);
    $plugin_definitions = $this->removeExcludeDefinitions($plugin_definitions);

    // Initialize and return all plugin instances.
    foreach ($plugin_definitions as $plugin_id => $plugin_definition) {
      $this->createInstance($plugin_id);
    }

    return $this->instances;
  }

  /**
   * {@inheritdoc}
   */
  public function initializeElement(array &$element) {
    $element_plugin = $this->getElementInstance($element);
    $element_plugin->initialize($element);
  }

  /**
   * {@inheritdoc}
   */
  public function buildElement(array &$element, array $form, FormStateInterface $form_state) {
    // Get the webform submission.
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = ($form_object instanceof WebformSubmissionForm) ? $form_object->getEntity() : NULL;
    $webform = ($webform_submission) ? $webform_submission->getWebform() : NULL;

    $element_plugin = $this->getElementInstance($element, $webform_submission ?: $webform);
    $element_plugin->prepare($element, $webform_submission);
    $element_plugin->finalize($element, $webform_submission);
    $element_plugin->setDefaultValue($element);

    // Allow modules to alter the webform element.
    // @see \Drupal\Core\Field\WidgetBase::formSingleElement()
    $hooks = ['webform_element'];
    if (!empty($element['#type'])) {
      $hooks[] = 'webform_element_' . $element['#type'];
    }
    $context = ['form' => $form];
    $this->moduleHandler->alter($hooks, $element, $form_state, $context);

    // Allow handlers to alter the webform element.
    if ($webform_submission) {
      $webform->invokeHandlers('alterElement', $element, $form_state, $context);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processElement(array &$element) {
    $element_plugin = $this->getElementInstance($element);
    $element_plugin->initialize($element);
    $element_plugin->prepare($element);
    $element_plugin->finalize($element);
    $element_plugin->setDefaultValue($element);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function processElements(array &$elements) {
    foreach ($elements as $key => &$element) {
      if (!WebformElementHelper::isElement($element, $key)) {
        continue;
      }

      // Process the webform element.
      $this->processElement($element);

      // Recurse and prepare nested elements.
      $this->processElements($element);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invokeMethod($method, array &$element, &$context1 = NULL, &$context2 = NULL) {
    // Make sure element has a #type.
    if (!isset($element['#type'])) {
      return NULL;
    }

    $plugin_id = $this->getElementPluginId($element);

    /** @var \Drupal\webform\Plugin\WebformElementInterface $webform_element */
    $webform_element = $this->createInstance($plugin_id);
    return $webform_element->$method($element, $context1, $context2);
  }

  /**
   * {@inheritdoc}
   */
  public function getElementPluginId(array $element) {
    if (isset($element['#webform_plugin_id']) && $this->hasDefinition($element['#webform_plugin_id'])) {
      return $element['#webform_plugin_id'];
    }
    elseif (isset($element['#type']) && $this->hasDefinition($element['#type'])) {
      return $element['#type'];
    }
    elseif (isset($element['#markup'])) {
      return 'webform_markup';
    }

    return $this->getFallbackPluginId(NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function getElementInstance(array $element, EntityInterface $entity = NULL) {
    $plugin_id = $this->getElementPluginId($element);

    /** @var \Drupal\webform\Plugin\WebformElementInterface $element_plugin */
    $element_plugin = $this->createInstance($plugin_id, $element);

    if ($entity) {
      $element_plugin->setEntities($entity);
    }
    else {
      $element_plugin->resetEntities();
    }

    return $element_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getSortedDefinitions(array $definitions = NULL, $sort_by = 'label') {
    $definitions = isset($definitions) ? $definitions : $this->getDefinitions();

    switch ($sort_by) {
      case 'category':
        uasort($definitions, function ($a, $b) use ($sort_by) {
          return strnatcasecmp($a['category'] . '-' . $a[$sort_by], $b['category'] . '-' . $b[$sort_by]);
        });
        break;

      default:
        uasort($definitions, function ($a, $b) use ($sort_by) {
          return strnatcasecmp($a[$sort_by], $b[$sort_by]);
        });
        break;
    }

    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupedDefinitions(array $definitions = NULL, $label_key = 'label') {
    /** @var \Drupal\Core\Plugin\CategorizingPluginManagerTrait|\Drupal\Component\Plugin\PluginManagerInterface $this */
    $definitions = $this->getSortedDefinitions(isset($definitions) ? $definitions : $this->getDefinitions(), $label_key);

    // Organize grouped definition with basic and advanced first and other last.
    $basic_category = (string) $this->t('Basic elements');
    $advanced_category = (string) $this->t('Advanced elements');
    $other_category = (string) $this->t('Other elements');

    $grouped_definitions = [
      $basic_category => [],
      $advanced_category => [],
    ];
    foreach ($definitions as $id => $definition) {
      $grouped_definitions[(string) $definition['category']][$id] = $definition;
    }
    if (isset($grouped_definitions[''])) {
      $no_category = $grouped_definitions[''];
      unset($grouped_definitions['']);
      $grouped_definitions += [$other_category => $no_category];
    }
    return $grouped_definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function removeExcludeDefinitions(array $definitions) {
    $definitions = isset($definitions) ? $definitions : $this->getDefinitions();
    $excluded = $this->configFactory->get('webform.settings')->get('element.excluded_elements');
    return $excluded ? array_diff_key($definitions, $excluded) : $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatableProperties() {
    $properties = [];
    $webform_elements = $this->getInstances();
    foreach ($webform_elements as $webform_element) {
      $translatable_properties = $webform_element->getTranslatableProperties();
      $properties += array_combine($translatable_properties, $translatable_properties);
    }
    ksort($properties);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllProperties() {
    $properties = [];
    $webform_elements = $this->getInstances();
    foreach ($webform_elements as $webform_element) {
      $default_properties = array_keys($webform_element->getDefaultProperties());
      $properties += array_combine($default_properties, $default_properties);
    }
    ksort($properties);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isExcluded($type) {
    return $this->configFactory->get('webform.settings')->get('element.excluded_elements.' . $type) ? TRUE : FALSE;
  }

}
