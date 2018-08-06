<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:image:url' meta tag.
 *
 * @MetatagTag(
 *   id = "og_image_url",
 *   label = @Translation("Image URL"),
 *   description = @Translation("A alternative version of og:image and has exactly the same requirements; only one needs to be used."),
 *   name = "og:image:url",
 *   group = "open_graph",
 *   weight = 10,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = TRUE
 * )
 */
class OgImageUrl extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
