<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * Provides a plugin for the 'canonical' meta tag.
 *
 * @MetatagTag(
 *   id = "canonical_url",
 *   label = @Translation("Canonical URL"),
 *   description = @Translation("A link to the preferred page location or URL of the content of this page, to help eliminate duplicate content penalties from search engines."),
 *   name = "canonical",
 *   group = "advanced",
 *   weight = 1,
 *   type = "uri",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class CanonicalUrl extends LinkRelBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
