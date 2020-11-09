<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:audio:type' meta tag.
 *
 * @MetatagTag(
 *   id = "og_audio_type",
 *   label = @Translation("Audio type"),
 *   description = @Translation("The MIME type of the audio file. Examples include 'application/mp3' for an MP3 file."),
 *   name = "og:audio:type",
 *   group = "open_graph",
 *   weight = 41,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class OgAudioType extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
