<?php

namespace Drupal\webform;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Element\WebformCompositeBase;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Storage controller class for "webform_options" configuration entities.
 */
class WebformOptionsStorage extends ConfigEntityStorage implements WebformOptionsStorageInterface {

  /**
   * A element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

  /**
   * The webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Cached list of composite elements with sub-elements that uses webform options.
   *
   * @var array
   */
  protected $usedByCompositeElements;

  /**
   * Cached list of webforms that uses webform options.
   *
   * @var array
   */
  protected $usedByWebforms;

  /**
   * Constructs a WebformOptionsStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info
   *   The element info manager.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $memory_cache
   *   The memory cache.
   *
   * @todo Webform 8.x-6.x: Move $memory_cache right after $language_manager.
   */
  public function __construct(EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, UuidInterface $uuid_service, LanguageManagerInterface $language_manager, ElementInfoManagerInterface $element_info, WebformElementManagerInterface $element_manager, MemoryCacheInterface $memory_cache = NULL) {
    parent::__construct($entity_type, $config_factory, $uuid_service, $language_manager, $memory_cache);
    $this->elementInfo = $element_info;
    $this->elementManager = $element_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('language_manager'),
      $container->get('plugin.manager.element_info'),
      $container->get('plugin.manager.webform.element'),
      $container->get('entity.memory_cache')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories() {
    $webform_options = $this->loadMultiple();
    $categories = [];
    foreach ($webform_options as $webform_option) {
      if ($category = $webform_option->get('category')) {
        $categories[$category] = $category;
      }
    }
    ksort($categories);
    return $categories;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    $webform_options = $this->loadMultiple();
    @uasort($webform_options, [$this->entityType->getClass(), 'sort']);

    $uncategorized_options = [];
    $categorized_options = [];
    foreach ($webform_options as $id => $webform_option) {
      if ($category = $webform_option->get('category')) {
        $categorized_options[$category][$id] = $webform_option->label();
      }
      else {
        $uncategorized_options[$id] = $webform_option->label();
      }
    }
    return $uncategorized_options + $categorized_options;
  }

  /**
   * {@inheritdoc}
   */
  public function getLikerts() {
    $webform_options = $this->loadByProperties(['likert' => TRUE]);
    @uasort($webform_options, [$this->entityType->getClass(), 'sort']);

    $likert_options = [];
    foreach ($webform_options as $id => $webform_option) {
      $likert_options[$id] = str_replace(t('Likert') . ': ', '', $webform_option->label());
    }
    return $likert_options;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsedByCompositeElements(WebformOptionsInterface $webform_options) {
    if (!isset($this->usedByCompositeElements)) {
      $this->usedByCompositeElements = [];
      $definitions = $this->elementInfo->getDefinitions();
      foreach (array_keys($definitions) as $plugin_id) {
        /** @var \Drupal\Core\Render\Element\ElementInterface $element */
        $element = $this->elementInfo->createInstance($plugin_id);
        // Make sure element is composite and not provided by the
        // webform_composite.module.
        if (!$element instanceof WebformCompositeBase || in_array($plugin_id, ['webform_composite'])) {
          continue;
        }

        $composite_elements = $element->getCompositeElements([]);
        foreach ($composite_elements as $composite_element_key => $composite_element) {
          if (isset($composite_element['#options'])) {
            $webform_element_definition = $this->elementManager->getDefinition($plugin_id);
            $f_args = [
              '@composite' => $webform_element_definition['label'],
              '@element' => $composite_element['#title'],
            ];
            $this->usedByCompositeElements[$composite_element_key]["$plugin_id:$composite_element_key"] = new FormattableMarkup('@composite (@element)', $f_args);
          }
        }
      }
    }

    $options_id = $webform_options->id();

    $used_by = [];
    foreach ($this->usedByCompositeElements as $key => $elements) {
      if (strpos($options_id, $key) === 0) {
        $used_by = array_merge($used_by, $elements);
      }
    }

    if ($used_by) {
      $used_by = array_unique($used_by);
      asort($used_by);
    }

    return $used_by;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsedByWebforms(WebformOptionsInterface $webform_options) {
    if (!isset($this->usedByWebforms)) {
      // Looping through webform configuration instead of webform entities to
      // improve performance.
      $this->usedByWebforms = [];
      foreach ($this->configFactory->listAll('webform.webform.') as $webform_config_name) {
        $config = $this->configFactory->get($webform_config_name);
        $element_data = Yaml::encode($config->get('elements'));
        if (preg_match_all('/(?:options|answers)\'\: ([a-z_]+)/', $element_data, $matches)) {
          $webform_id = $config->get('id');
          $webform_title = $config->get('title');
          foreach ($matches[1] as $options_id) {
            $this->usedByWebforms[$options_id][$webform_id] = $webform_title;
          }
        }
      }
    }

    $options_id = $webform_options->id();
    return (isset($this->usedByWebforms[$options_id])) ? $this->usedByWebforms[$options_id] : [];
  }

}
