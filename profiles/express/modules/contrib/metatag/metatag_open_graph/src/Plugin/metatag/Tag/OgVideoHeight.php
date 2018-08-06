<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:video:height' meta tag.
 *
 * @MetatagTag(
 *   id = "og_video_height",
 *   label = @Translation("Video height"),
 *   description = @Translation("The height of the above video(s). Note: if both the unsecured and secured videos are provided, they should both be the same size."),
 *   name = "og:video:height",
 *   group = "open_graph",
 *   weight = 14,
 *   type = "integer",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class OgVideoHeight extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
