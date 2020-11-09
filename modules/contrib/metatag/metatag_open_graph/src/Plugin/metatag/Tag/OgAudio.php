<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:audio' meta tag.
 *
 * @MetatagTag(
 *   id = "og_audio",
 *   label = @Translation("Audio URL"),
 *   description = @Translation("The URL to an audio file that complements this object."),
 *   name = "og:audio",
 *   group = "open_graph",
 *   weight = 39,
 *   type = "uri",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class OgAudio extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
