<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'book:isbn' meta tag.
 *
 * @MetatagTag(
 *   id = "book_isbn",
 *   label = @Translation("ISBN"),
 *   description = @Translation("The Book's ISBN"),
 *   name = "book:isbn",
 *   group = "open_graph",
 *   weight = 36,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class BookISBN extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
