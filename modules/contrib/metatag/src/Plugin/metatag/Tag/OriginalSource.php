<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * The advanced "Original Source" meta tag.
 *
 * @MetatagTag(
 *   id = "original_source",
 *   label = @Translation("Original source"),
 *   description = @Translation("Used to indicate the URL that broke the story, and can link to either an internal URL or an external source. If the full URL is not known it is acceptable to use a partial URL or just the domain name."),
 *   name = "original-source",
 *   group = "advanced",
 *   weight = 4,
 *   type = "uri",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class OriginalSource extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
