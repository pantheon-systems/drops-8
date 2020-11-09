<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'video:actor' meta tag.
 *
 * @MetatagTag(
 *   id = "video_actor",
 *   label = @Translation("Actor(s)"),
 *   description = @Translation("Links to the Facebook profiles for actor(s) that appear in the video."),
 *   name = "video:actor",
 *   group = "open_graph",
 *   weight = 45,
 *   type = "uri",
 *   secure = FALSE,
 *   multiple = TRUE
 * )
 */
class VideoActor extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
