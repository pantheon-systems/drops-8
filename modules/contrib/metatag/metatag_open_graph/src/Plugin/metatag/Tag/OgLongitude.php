<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:longitude' meta tag.
 *
 * @MetatagTag(
 *   id = "og_longitude",
 *   label = @Translation("Longitude"),
 *   description = @Translation(""),
 *   name = "og:longitude",
 *   group = "open_graph",
 *   weight = 16,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class OgLongitude extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
