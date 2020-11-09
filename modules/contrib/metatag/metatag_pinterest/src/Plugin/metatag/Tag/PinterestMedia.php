<?php

namespace Drupal\metatag_pinterest\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'pin:media' meta tag.
 *
 * @MetatagTag(
 *   id = "pinterest_media",
 *   label = @Translation("Media"),
 *   description = @Translation("The URL of media which should represent the content."),
 *   name = "pin:media",
 *   group = "pinterest",
 *   weight = 6,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class PinterestMedia extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
