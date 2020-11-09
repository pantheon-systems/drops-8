<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'video:tag' meta tag.
 *
 * @MetatagTag(
 *   id = "video_tag",
 *   label = @Translation("Tag words"),
 *   description = @Translation("Tag words associated with this video."),
 *   name = "video:tag",
 *   group = "open_graph",
 *   weight = 49,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE
 * )
 */
class VideoTag extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
