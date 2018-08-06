<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * The Open Graph "Article tag" meta tag.
 *
 * @MetatagTag(
 *   id = "article_tag",
 *   label = @Translation("Article tag(s)"),
 *   description = @Translation("Appropriate keywords for this content."),
 *   name = "article:tag",
 *   group = "open_graph",
 *   weight = 31,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = TRUE
 * )
 */
class ArticleTag extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
