<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:image:type' meta tag.
 *
 * @MetatagTag(
 *   id = "og_image_type",
 *   label = @Translation("Image type"),
 *   description = @Translation("The type of image referenced above. Should be either 'image/gif' for a GIF image, 'image/jpeg' for a JPG/JPEG image, or 'image/png' for a PNG image. Note: there should be one value for each image, and having more than there are images may cause problems."),
 *   name = "og:image:type",
 *   group = "open_graph",
 *   weight = 12,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class OgImageType extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
