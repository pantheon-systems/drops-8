<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * The Open Graph "Article expiration time" meta tag.
 *
 * @MetatagTag(
 *   id = "article_expiration_time",
 *   label = @Translation("Article expiration date & time"),
 *   description = @Translation("The date this content will expire, with an optional time value. Needs to be in <a href='http://en.wikipedia.org/wiki/ISO_8601'>ISO 8601</a> format."),
 *   name = "article:expiration_time",
 *   group = "open_graph",
 *   weight = 34,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class ArticleExpirationTime extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
