<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:locale:alternate' meta tag.
 *
 * @MetatagTag(
 *   id = "og_locale_alternative",
 *   label = @Translation("Alternative locales"),
 *   description = @Translation("Other locales this content is available in, must be in the format language_TERRITORY, e.g. 'fr_FR'."),
 *   name = "og:locale:alternate",
 *   group = "open_graph",
 *   weight = 27,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE
 * )
 */
class OgLocaleAlternative extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
