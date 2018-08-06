<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * The Open Graph "Title" meta tag.
 *
 * @MetatagTag(
 *   id = "og_title",
 *   label = @Translation("Title"),
 *   description = @Translation("The title of the content, e.g., <em>The Rock</em>."),
 *   name = "og:title",
 *   group = "open_graph",
 *   weight = 4,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class OgTitle extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
