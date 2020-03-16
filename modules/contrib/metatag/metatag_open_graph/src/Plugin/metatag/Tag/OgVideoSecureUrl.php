<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:video:secure_url' meta tag.
 *
 * @MetatagTag(
 *   id = "og_video_secure_url",
 *   label = @Translation("Video Secure URL"),
 *   description = @Translation("The secure URL (HTTPS) of an video which should represent the content. The video must be at least 50px by 50px and have a maximum aspect ratio of 3:1. Supports PNG, JPEG and GIF formats. All 'http://' URLs will automatically be converted to 'https://'."),
 *   name = "og:video:secure_url",
 *   group = "open_graph",
 *   weight = 11,
 *   type = "video",
 *   secure = TRUE,
 *   multiple = FALSE,
 *   absolute_url = TRUE
 * )
 */
class OgVideoSecureUrl extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
