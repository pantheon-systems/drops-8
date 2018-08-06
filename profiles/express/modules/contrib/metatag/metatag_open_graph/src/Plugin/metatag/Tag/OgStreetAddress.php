<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:street_address' meta tag.
 *
 * @MetatagTag(
 *   id = "og_street_address",
 *   label = @Translation("Street address"),
 *   description = @Translation(""),
 *   name = "og:street_address",
 *   group = "open_graph",
 *   weight = 18,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class OgStreetAddress extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
