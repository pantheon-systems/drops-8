<?php

namespace Drupal\metatag_google_cse\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'thumbnail' meta tag.
 *
 * @MetatagTag(
 *   id = "thumbnail",
 *   label = @Translation("Thumbnail"),
 *   description = @Translation("Use a url of a valid image."),
 *   name = "thumbnail",
 *   group = "google_cse",
 *   weight = 0,
 *   type = "uri",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Thumbnail extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
