<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * The basic "Refresh" meta tag.
 *
 * @MetatagTag(
 *   id = "refresh",
 *   label = @Translation("Refresh"),
 *   description = @Translation("The number of seconds to wait before refreshing the page. May also force redirect to another page using the format '5; url=https://example.com/', which would be triggered after five seconds."),
 *   name = "refresh",
 *   group = "advanced",
 *   weight = 5,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Refresh extends MetaHttpEquivBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
