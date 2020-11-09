<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * Provides a plugin for the 'next' meta tag.
 *
 * @MetatagTag(
 *   id = "next",
 *   label = @Translation("Next page URL"),
 *   description = @Translation("Used for paginated content by providing URL with rel='next' link."),
 *   name = "next",
 *   group = "advanced",
 *   weight = 2,
 *   type = "uri",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Next extends LinkRelBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
