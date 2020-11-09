<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:image:secure_url' meta tag.
 *
 * @MetatagTag(
 *   id = "og_image_secure_url",
 *   label = @Translation("Image Secure URL"),
 *   description = @Translation("The secure URL (HTTPS) of an image which should represent the content. The image must be at least 200 x 200 pixels in size; 600 x 316 pixels is a recommended minimum size, and for best results use an image least 1200 x 630 pixels in size. Supports PNG, JPEG and GIF formats."),
 *   name = "og:image:secure_url",
 *   group = "open_graph",
 *   weight = 11,
 *   type = "image",
 *   secure = TRUE,
 *   multiple = TRUE,
 *   absolute_url = TRUE
 * )
 */
class OgImageSecureUrl extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
