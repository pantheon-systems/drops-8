<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * The Open Graph "Article published time" meta tag.
 *
 * @MetatagTag(
 *   id = "article_published_time",
 *   label = @Translation("Article publication date & time"),
 *   description = @Translation("The date this content was published on, with an optional time value. Needs to be in <a href='https://en.wikipedia.org/wiki/ISO_8601'>ISO 8601</a> format."),
 *   name = "article:published_time",
 *   group = "open_graph",
 *   weight = 32,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class ArticlePublishedTime extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
