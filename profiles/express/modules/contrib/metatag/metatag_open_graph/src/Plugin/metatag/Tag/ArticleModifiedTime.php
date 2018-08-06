<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * The Open Graph "Article modified time" meta tag.
 *
 * @MetatagTag(
 *   id = "article_modified_time",
 *   label = @Translation("Article modification date & time"),
 *   description = @Translation("The date this content was last modified, with an optional time value. Needs to be in <a href='http://en.wikipedia.org/wiki/ISO_8601'>ISO 8601</a> format."),
 *   name = "article:modified_time",
 *   group = "open_graph",
 *   weight = 33,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class ArticleModifiedTime extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
