<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * The Open Graph "Article publisher" meta tag.
 *
 * @MetatagTag(
 *   id = "article_publisher",
 *   label = @Translation("Article publisher"),
 *   description = @Translation("Links an article to a publisher's Facebook page."),
 *   name = "article:publisher",
 *   group = "open_graph",
 *   weight = 29,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class ArticlePublisher extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
