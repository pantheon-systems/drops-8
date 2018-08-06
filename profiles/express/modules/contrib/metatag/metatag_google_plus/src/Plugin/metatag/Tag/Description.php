<?php

namespace Drupal\metatag_google_plus\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaItempropBase;

/**
 * The GooglePlus "Description" meta tag.
 *
 * @MetatagTag(
 *   id = "google_plus_description",
 *   label = @Translation("Description"),
 *   description = @Translation("Content description less than 200 characters."),
 *   name = "itemprop:description",
 *   group = "google_plus",
 *   weight = 2,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Description extends MetaItempropBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
