<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * The Open Graph "Book author" meta tag.
 *
 * @MetatagTag(
 *   id = "book_author",
 *   label = @Translation("Book author"),
 *   description = @Translation("Links a book to an author's Facebook profile, should be either URLs to the author's profile page or their Facebook profile IDs."),
 *   name = "book:author",
 *   group = "open_graph",
 *   weight = 35,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = TRUE
 * )
 */
class BookAuthor extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
