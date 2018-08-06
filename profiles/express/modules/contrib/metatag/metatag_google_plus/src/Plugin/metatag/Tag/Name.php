<?php

namespace Drupal\metatag_google_plus\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaItempropBase;

/**
 * The GooglePlus 'name' meta tag.
 *
 * @MetatagTag(
 *   id = "google_plus_name",
 *   label = @Translation("Name"),
 *   description = @Translation("Content title."),
 *   name = "itemprop:name",
 *   group = "google_plus",
 *   weight = 1,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Name extends MetaItempropBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
