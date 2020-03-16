<?php

namespace Drupal\metatag_pinterest\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'pin:url' meta tag.
 *
 * @MetatagTag(
 *   id = "pinterest_url",
 *   label = @Translation("URL"),
 *   description = @Translation("The URL which should represent the content."),
 *   name = "pin:url",
 *   group = "pinterest",
 *   weight = 7,
 *   type = "uri",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class PinterestUrl extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
