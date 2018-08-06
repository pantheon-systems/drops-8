<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:locale' meta tag.
 *
 * @MetatagTag(
 *   id = "og_locale",
 *   label = @Translation("Locale"),
 *   description = @Translation("The locale these tags are marked up in, must be in the format language_TERRITORY. Default is 'en_US'."),
 *   name = "og:locale",
 *   group = "open_graph",
 *   weight = 26,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class OgLocale extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
