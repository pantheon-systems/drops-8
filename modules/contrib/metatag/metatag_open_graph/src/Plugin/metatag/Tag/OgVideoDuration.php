<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:video:duration' meta tag.
 *
 * @MetatagTag(
 *   id = "og_video_duration",
 *   label = @Translation("Video duration (seconds)"),
 *   description = @Translation("The length of the video in seconds"),
 *   name = "og:video:duration",
 *   group = "open_graph",
 *   weight = 15,
 *   type = "integer",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class OgVideoDuration extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
