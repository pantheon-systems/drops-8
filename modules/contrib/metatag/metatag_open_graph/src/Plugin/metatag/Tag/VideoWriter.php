<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'video:writer' meta tag.
 *
 * @MetatagTag(
 *   id = "video_writer",
 *   label = @Translation("Scriptwriter(s)"),
 *   description = @Translation("Links to the Facebook profiles for scriptwriter(s) for the video."),
 *   name = "video:writer",
 *   group = "open_graph",
 *   weight = 50,
 *   type = "uri",
 *   secure = FALSE,
 *   multiple = TRUE
 * )
 */
class VideoWriter extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
