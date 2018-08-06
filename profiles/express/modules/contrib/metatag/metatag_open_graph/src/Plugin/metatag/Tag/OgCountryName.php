<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:country_name' meta tag.
 *
 * @MetatagTag(
 *   id = "og_country_name",
 *   label = @Translation("Country name"),
 *   description = @Translation(""),
 *   name = "og:country_name",
 *   group = "open_graph",
 *   weight = 22,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class OgCountryName extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
