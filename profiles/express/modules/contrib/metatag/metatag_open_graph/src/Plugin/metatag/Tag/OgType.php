<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * The Open Graph "Type" meta tag.
 *
 * @MetatagTag(
 *   id = "og_type",
 *   label = @Translation("Content type"),
 *   description = @Translation("The type of the content, e.g., <em>movie</em>."),
 *   name = "og:type",
 *   group = "open_graph",
 *   weight = 2,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class OgType extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
