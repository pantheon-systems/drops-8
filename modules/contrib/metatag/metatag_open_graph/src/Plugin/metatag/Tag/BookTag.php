<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * The Open Graph "Book tag" meta tag.
 *
 * @MetatagTag(
 *   id = "book_tag",
 *   label = @Translation("Book tag(s)"),
 *   description = @Translation("Appropriate keywords for this content."),
 *   name = "book:tag",
 *   group = "open_graph",
 *   weight = 38,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = TRUE
 * )
 */
class BookTag extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
