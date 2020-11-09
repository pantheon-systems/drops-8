<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * The Open Graph "Article author" meta tag.
 *
 * @MetatagTag(
 *   id = "article_author",
 *   label = @Translation("Article author"),
 *   description = @Translation("Links an article to an author's Facebook profile, should be either URLs to the author's profile page or their Facebook profile IDs."),
 *   name = "article:author",
 *   group = "open_graph",
 *   weight = 28,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = TRUE
 * )
 */
class ArticleAuthor extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
