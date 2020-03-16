<?php

namespace Drupal\metatag_hreflang\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;

/**
 * Create a new hreflang tag plugin for each enabled language.
 */
class HreflangDeriver extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Get a list of all defined languages.
    $languages = \Drupal::languageManager()
      ->getLanguages(LanguageInterface::STATE_ALL);

    // Now we loop over them and declare the derivatives.
    /** @var \Drupal\Core\Language\LanguageInterface $language */
    foreach ($languages as $langcode => $language) {
      // Ignore the global values.
      if ($langcode == Language::LANGCODE_NOT_SPECIFIED) {
        continue;
      }
      elseif ($langcode == Language::LANGCODE_NOT_APPLICABLE) {
        continue;
      }

      // The base definition includes the annotations defined in the plugin,
      // i.e. HreflangPerLanguage. Each one may be overridden.
      $derivative = $base_plugin_definition;

      // Here we fill in any missing keys on the layout annotation.
      $derivative['weight']++;
      $derivative['id'] = 'hreflang_' . $langcode;
      // The 'name' value is used as the value of the 'hreflang' attribute on
      // the HTML tag.
      $derivative['name'] = $langcode;
      $derivative['label'] = t("URL for a version of this page in %langcode", ['%langcode' => $language->getName()]);
      $derivative['description'] = '';

      // Reference derivatives based on their UUID instead of the record ID.
      $this->derivatives[$derivative['id']] = $derivative;
    }

    return $this->derivatives;
  }

}
