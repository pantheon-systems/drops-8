<?php

namespace Drupal\metatag_pinterest\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'pin:id' meta tag.
 *
 * @MetatagTag(
 *   id = "pinterest_id",
 *   label = @Translation("Id"),
 *   description = @Translation("The Canonical Pinterest object to pin."),
 *   name = "pin:id",
 *   group = "pinterest",
 *   weight = 5,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class PinterestId extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
