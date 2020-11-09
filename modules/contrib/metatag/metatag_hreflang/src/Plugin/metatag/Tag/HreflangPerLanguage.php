<?php

namespace Drupal\metatag_hreflang\Plugin\metatag\Tag;

/**
 * A new hreflang tag will be made available for each language.
 *
 * The meta tag's values will be based upon this annotation.
 *
 * @MetatagTag(
 *   id = "hreflang_per_language",
 *   deriver = "Drupal\metatag_hreflang\Plugin\Derivative\HreflangDeriver",
 *   label = @Translation("Hreflang per language"),
 *   description = @Translation("This plugin will be cloned from these settings for each enabled language."),
 *   name = "hreflang_per_language",
 *   group = "hreflang",
 *   weight = 1,
 *   type = "uri",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class HreflangPerLanguage extends HreflangBase {
  // Everything will be inherited.
}
