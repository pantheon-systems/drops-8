<?php

namespace Drupal\metatag_google_plus\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\LinkRelBase;

/**
 * The basic "Author" meta tag.
 *
 * @MetatagTag(
 *   id = "google_plus_author",
 *   label = @Translation("Author"),
 *   description = @Translation("Used by some search engines to confirm authorship of the content on a page. Should be either the full URL for the author's Google+ profile page or a local page with information about the author."),
 *   name = "author",
 *   group = "google_plus",
 *   weight = 4,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Author extends LinkRelBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
