<?php

namespace Drupal\metatag_google_cse\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'google_rating' meta tag.
 *
 * @MetatagTag(
 *   id = "google_rating",
 *   label = @Translation("Content rating"),
 *   description = @Translation("Works only with numeric values."),
 *   name = "rating",
 *   group = "google_cse",
 *   weight = 0,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class GoogleRating extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
