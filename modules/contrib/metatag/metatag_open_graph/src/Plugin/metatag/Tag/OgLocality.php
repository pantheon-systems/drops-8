<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:locality' meta tag.
 *
 * @MetatagTag(
 *   id = "og_locality",
 *   label = @Translation("Locality"),
 *   description = @Translation(""),
 *   name = "og:locality",
 *   group = "open_graph",
 *   weight = 19,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class OgLocality extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
