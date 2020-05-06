<?php

namespace Drupal\webform;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformYaml;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Defines a class to translate webform elements.
 */
class WebformTranslationManager implements WebformTranslationManagerInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * An array on translatable properties.
   *
   * @var array
   */
  protected $translatableProperties;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a WebformTranslationManager object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   */
  public function __construct(RouteMatchInterface $route_match, LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory, MessengerInterface $messenger, WebformElementManagerInterface $element_manager) {
    $this->routeMatch = $route_match;
    $this->languageManager = $language_manager;
    $this->configFactory = $config_factory;
    $this->messenger = $messenger;
    $this->elementManager = $element_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function isAdminRoute() {
    $route_name = $this->routeMatch->getRouteName();

    // Don't initialize translation on webform CRUD routes.
    if (preg_match('/^entity\.webform\.(?:edit_form|duplicate_form|delete_form)$/', $route_name)) {
      return TRUE;
    }

    // Don't initialize translation on webform UI routes.
    if (strpos($route_name, 'entity.webform_ui.') === 0) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getElements(WebformInterface $webform, $langcode = NULL, $reset = FALSE) {
    // Note: Below code return the default languages elements for missing
    // translations.
    $config_override_language = $this->languageManager->getConfigOverrideLanguage();
    $config_name = 'webform.webform.' . $webform->id();

    // Set langcode from original langcode.
    if (!$langcode) {
      $langcode = $this->getOriginalLangcode($webform);
    }

    // Reset cached config.
    if ($reset) {
      $this->configFactory->reset($config_name);
    }

    $this->languageManager->setConfigOverrideLanguage($this->languageManager->getLanguage($langcode));
    $elements = $this->configFactory->get($config_name)->get('elements');
    $this->languageManager->setConfigOverrideLanguage($config_override_language);

    if (!$elements) {
      return [];
    }
    elseif ($error = WebformYaml::validate($elements)) {
      $this->messenger->addError($error);
      return [];
    }
    else {
      return Yaml::decode($elements);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseElements(WebformInterface $webform) {
    $default_langcode = $this->getOriginalLangcode($webform) ?: $this->languageManager->getDefaultLanguage()->getId();
    $config_elements = $this->getElements($webform, $default_langcode);
    $elements = WebformElementHelper::getFlattened($config_elements);
    foreach ($elements as $element_key => &$element) {
      // Always include composite element's default '#{element}__title'.
      $element_plugin = $this->elementManager->getElementInstance($element);
      if ($element_plugin instanceof WebformCompositeBase) {
        $composite_elements = $element_plugin->getCompositeElements();
        foreach ($composite_elements as $composite_key => $composite_element) {
          $property_key = $composite_key . '__title';
          if (empty($element["#$property_key"])) {
            $element["#$property_key"] = $element_plugin->getDefaultProperty($property_key);
          }
        }
      }

      $this->removeUnTranslatablePropertiesFromElement($element);
      if (empty($element)) {
        unset($elements[$element_key]);
      }
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceElements(WebformInterface $webform) {
    return $this->getBaseElements($webform);
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslationElements(WebformInterface $webform, $langcode) {
    $elements = $this->getSourceElements($webform);
    $translation_elements = $this->getElements($webform, $langcode);
    if ($elements == $translation_elements) {
      return $elements;
    }
    WebformElementHelper::merge($elements, $translation_elements);
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalLangcode(WebformInterface $webform) {
    // NOTE: Can't inject ConfigMapperInterface because it requires that
    // config_translation.module to be installed.
    /** @var \Drupal\config_translation\ConfigMapperInterface $mapper */
    $mapper = \Drupal::service('plugin.manager.config_translation.mapper')->createInstance('webform');
    $mapper->addConfigName('webform.webform.' . $webform->id());
    return $mapper->getLangcode();
  }

  /****************************************************************************/
  // Translatable properties helpers.
  /****************************************************************************/

  /**
   * Remove untranslatable properties form an element.
   *
   * @param array $element
   *   An element.
   */
  protected function removeUnTranslatablePropertiesFromElement(array &$element) {
    $translatable_properties = $this->getTranslatableProperties();

    $element_type = (isset($element['#type'])) ? $element['#type'] : NULL;
    foreach ($element as $property_key => $property_value) {
      $translatable_property_key = $property_key;

      // If translatable property key is a sub element (ex: subelement__title)
      // get the sub element's translatable property key.
      if (preg_match('/^.*__(.*)$/', $translatable_property_key, $match)) {
        $translatable_property_key = '#' . $match[1];
      }

      if (in_array($translatable_property_key, ['#options', '#answers']) && is_string($property_value)) {
        // Unset options and answers that are webform option ids.
        unset($element[$property_key]);
      }
      elseif ($translatable_property_key === '#element' && $element_type === 'webform_custom_composite') {
        foreach ($element[$property_key] as &$composite_element_value) {
          $this->removeUnTranslatablePropertiesFromElement($composite_element_value);
        }
      }
      elseif (!isset($translatable_properties[$translatable_property_key])) {
        // Unset none translatable properties.
        unset($element[$property_key]);
      }
    }
  }

  /**
   * Get translated properties from element manager.
   *
   * @return array
   *   An array of translated properties prefixed with a hashes (#).
   */
  protected function getTranslatableProperties() {
    if ($this->translatableProperties) {
      return $this->translatableProperties;
    }

    $this->translatableProperties = WebformArrayHelper::addPrefix($this->elementManager->getTranslatableProperties());
    return $this->translatableProperties;
  }

}
