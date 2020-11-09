<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'video:series' meta tag.
 *
 * @MetatagTag(
 *   id = "video_series",
 *   label = @Translation("Series"),
 *   description = @Translation("The TV show this series belongs to."),
 *   name = "video:series",
 *   group = "open_graph",
 *   weight = 48,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class VideoSeries extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
