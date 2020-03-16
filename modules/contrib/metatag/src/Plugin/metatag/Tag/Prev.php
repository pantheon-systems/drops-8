<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * Provides a plugin for the 'prev' meta tag.
 *
 * @MetatagTag(
 *   id = "prev",
 *   label = @Translation("Previous page URL"),
 *   description = @Translation("Used for paginated content by providing URL with rel='prev' link."),
 *   name = "prev",
 *   group = "advanced",
 *   weight = 2,
 *   type = "uri",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Prev extends LinkRelBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
