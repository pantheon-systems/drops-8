<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:image:width' meta tag.
 *
 * @MetatagTag(
 *   id = "og_image_width",
 *   label = @Translation("Image width"),
 *   description = @Translation("The height of the above image(s). Note: if both the unsecured and secured images are provided, they should both be the same size."),
 *   name = "og:image:width",
 *   group = "open_graph",
 *   weight = 13,
 *   type = "integer",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class OgImageWidth extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
