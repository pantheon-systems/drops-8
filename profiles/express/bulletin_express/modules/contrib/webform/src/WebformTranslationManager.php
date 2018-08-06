<?php

namespace Drupal\webform;

use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformYaml;

/**
 * Defines a class to translate webform elements.
 */
class WebformTranslationManager implements WebformTranslationManagerInterface {

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
   * @var \Drupal\webform\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Constructs a WebformTranslationManager object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\webform\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   */
  public function __construct(LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory, WebformElementManagerInterface $element_manager) {
    $this->languageManager = $language_manager;
    $this->configFactory = $config_factory;
    $this->elementManager = $element_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigElements(WebformInterface $webform, $langcode = NULL, $reset = FALSE) {
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
      drupal_set_message($error, 'error');
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
    $config_elements = $this->getConfigElements($webform, $default_langcode);
    $elements = WebformElementHelper::getFlattened($config_elements);
    $translatable_properties = WebformArrayHelper::addPrefix($this->elementManager->getTranslatableProperties());
    foreach ($elements as $element_key => &$element) {
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
        elseif (!isset($translatable_properties[$translatable_property_key])) {
          // Unset none translatble properties.
          unset($element[$property_key]);
        }
      }
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
    $elements = $this->getBaseElements($webform);
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslationElements(WebformInterface $webform, $langcode) {
    $elements = $this->getSourceElements($webform);
    $translation_elements = $this->getConfigElements($webform, $langcode);
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
    // NOTE: Can't inject ConfigMapperInterface  because it requires that
    // config_translation.module to be installed.
    /** @var \Drupal\config_translation\ConfigMapperInterface $mapper */
    $mapper = \Drupal::service('plugin.manager.config_translation.mapper')->createInstance('webform');
    $mapper->addConfigName('webform.webform.' . $webform->id());
    return $mapper->getLangcode();
  }

}
