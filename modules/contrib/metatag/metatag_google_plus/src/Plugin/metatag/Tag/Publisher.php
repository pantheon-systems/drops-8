<?php

namespace Drupal\metatag_google_plus\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\LinkRelBase;

/**
 * Provides a plugin for the 'publisher' meta tag.
 *
 * @MetatagTag(
 *   id = "google_plus_publisher",
 *   label = @Translation("Publisher URL"),
 *   description = @Translation("Used by some search engines to confirm publication of the content on a page. Should be the full URL for the publication's Google+ profile page."),
 *   name = "publisher",
 *   group = "google_plus",
 *   weight = 5,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Publisher extends LinkRelBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
