<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:audio:secure_url' meta tag.
 *
 * @MetatagTag(
 *   id = "og_audio_secure_url",
 *   label = @Translation("Audio secure URL"),
 *   description = @Translation("The secure URL to an audio file that complements this object. All 'http://' URLs will automatically be converted to 'https://'."),
 *   name = "og:audio:secure_url",
 *   group = "open_graph",
 *   weight = 40,
 *   type = "uri",
 *   secure = TRUE,
 *   multiple = FALSE
 * )
 */
class OgAudioSecureUrl extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
