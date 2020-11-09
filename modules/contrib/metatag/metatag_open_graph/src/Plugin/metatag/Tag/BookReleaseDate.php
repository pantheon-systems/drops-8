<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'book:release_date' meta tag.
 *
 * @MetatagTag(
 *   id = "book_release_date",
 *   label = @Translation("Release Date"),
 *   description = @Translation("The date the book was released."),
 *   name = "book:release_date",
 *   group = "open_graph",
 *   weight = 37,
 *   type = "date",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class BookReleaseDate extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
