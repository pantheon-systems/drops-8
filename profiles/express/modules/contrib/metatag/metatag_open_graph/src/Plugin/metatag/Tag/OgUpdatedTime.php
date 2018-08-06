<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:updated_time' meta tag.
 *
 * @MetatagTag(
 *   id = "og_updated_time",
 *   label = @Translation("Content modification date & time"),
 *   description = @Translation("The date this content was last modified, with an optional time value. Needs to be in <a href='http://en.wikipedia.org/wiki/ISO_8601'>ISO 8601</a> format. Can be the same as the 'Article modification date' tag."),
 *   name = "og:updated_time",
 *   group = "open_graph",
 *   weight = 15,
 *   type = "date",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class OgUpdatedTime extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
