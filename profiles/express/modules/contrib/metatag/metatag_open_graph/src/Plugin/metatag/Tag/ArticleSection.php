<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * The Open Graph "Article section" meta tag.
 *
 * @MetatagTag(
 *   id = "article_section",
 *   label = @Translation("Article section"),
 *   description = @Translation("The primary section of this website the content belongs to."),
 *   name = "article:section",
 *   group = "open_graph",
 *   weight = 30,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class ArticleSection extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
