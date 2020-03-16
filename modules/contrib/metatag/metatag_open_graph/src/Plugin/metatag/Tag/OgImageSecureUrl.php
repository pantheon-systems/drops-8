<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:image:secure_url' meta tag.
 *
 * @MetatagTag(
 *   id = "og_image_secure_url",
 *   label = @Translation("Image Secure URL"),
 *   description = @Translation("The secure URL (HTTPS) of an image which should represent the content. The image must be at least 50px by 50px and have a maximum aspect ratio of 3:1. Supports PNG, JPEG and GIF formats. All 'http://' URLs will automatically be converted to 'https://'."),
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
