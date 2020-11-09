<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'video:director' meta tag.
 *
 * @MetatagTag(
 *   id = "video_director",
 *   label = @Translation("Director(s)"),
 *   description = @Translation("Links to the Facebook profiles for director(s) that worked on the video."),
 *   name = "video:director",
 *   group = "open_graph",
 *   weight = 47,
 *   type = "uri",
 *   secure = FALSE,
 *   multiple = TRUE
 * )
 */
class VideoDirector extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
