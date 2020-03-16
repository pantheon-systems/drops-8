<?php

namespace Drupal\metatag_google_cse\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'audience' meta tag.
 *
 * @MetatagTag(
 *   id = "audience",
 *   label = @Translation("Content audience"),
 *   description = @Translation("The content audience, e.g. ""all""."),
 *   name = "audience",
 *   group = "google_cse",
 *   weight = 0,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Audience extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
