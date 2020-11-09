<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'video:release_date' meta tag.
 *
 * @MetatagTag(
 *   id = "video_release_date",
 *   label = @Translation("Release date"),
 *   description = @Translation("The date the video was released."),
 *   name = "video:release_date",
 *   group = "open_graph",
 *   weight = 48,
 *   type = "date",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class VideoReleaseDate extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
