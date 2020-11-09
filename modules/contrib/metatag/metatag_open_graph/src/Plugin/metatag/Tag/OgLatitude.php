<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:latitude' meta tag.
 *
 * @MetatagTag(
 *   id = "og_latitude",
 *   label = @Translation("Latitude"),
 *   description = @Translation(""),
 *   name = "og:latitude",
 *   group = "open_graph",
 *   weight = 16,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class OgLatitude extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
