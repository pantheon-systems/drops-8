<?php

namespace Drupal\metatag_pinterest\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'pin:description' meta tag.
 *
 * @MetatagTag(
 *   id = "pinterest_description",
 *   label = @Translation("Description"),
 *   description = @Translation("A one to two sentence description of the content."),
 *   name = "pin:description",
 *   group = "pinterest",
 *   weight = 8,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class PinterestDescription extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
