<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:image:height' meta tag.
 *
 * @MetatagTag(
 *   id = "og_image_height",
 *   label = @Translation("Image height"),
 *   description = @Translation("The height of the above image(s). Note: if both the unsecured and secured images are provided, they should both be the same size."),
 *   name = "og:image:height",
 *   group = "open_graph",
 *   weight = 14,
 *   type = "integer",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class OgImageHeight extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
