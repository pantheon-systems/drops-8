<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:image' meta tag.
 *
 * @MetatagTag(
 *   id = "og_image",
 *   label = @Translation("Image"),
 *   description = @Translation("The URL of an image which should represent the content. The image must be at least 200 x 200 pixels in size; 600 x 316 pixels is a recommended minimum size, and for best results use an image least 1200 x 630 pixels in size. Supports PNG, JPEG and GIF formats. Should not be used if og:image:url is used. Note: if multiple images are added many services (e.g. Facebook) will default to the largest image, not specifically the first one."),
 *   name = "og:image",
 *   group = "open_graph",
 *   weight = 9,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   absolute_url = TRUE
 * )
 */
class OgImage extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
