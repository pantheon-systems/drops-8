<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:video:width' meta tag.
 *
 * @MetatagTag(
 *   id = "og_video_width",
 *   label = @Translation("Video width"),
 *   description = @Translation("The height of the above video(s). Note: if both the unsecured and secured videos are provided, they should both be the same size."),
 *   name = "og:video:width",
 *   group = "open_graph",
 *   weight = 13,
 *   type = "integer",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class OgVideoWidth extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
