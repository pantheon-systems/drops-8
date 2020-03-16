<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:video:type' meta tag.
 *
 * @MetatagTag(
 *   id = "og_video_type",
 *   label = @Translation("Video type"),
 *   description = @Translation("The type of video referenced above. Should be either  video.episode, video.movie, video.other, and video.tv_show. Note: there should be one value for each video, and having more than there are videos may cause problems."),
 *   name = "og:video:type",
 *   group = "open_graph",
 *   weight = 12,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class OgVideoType extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
