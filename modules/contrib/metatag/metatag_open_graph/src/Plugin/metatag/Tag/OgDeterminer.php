<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:determiner' meta tag.
 *
 * @MetatagTag(
 *   id = "og_determiner",
 *   label = @Translation("Determiner"),
 *   description = @Translation("The word that appears before the content's title in a sentence. The default ignores this value, the 'Automatic' value should be sufficient if this is actually needed."),
 *   name = "og:determiner",
 *   group = "open_graph",
 *   weight = 0,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class OgDeterminer extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
