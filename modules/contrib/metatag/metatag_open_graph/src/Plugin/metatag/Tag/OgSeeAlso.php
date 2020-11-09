<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:see_also' meta tag.
 *
 * @MetatagTag(
 *   id = "og_see_also",
 *   label = @Translation("See also"),
 *   description = @Translation("URLs to related content"),
 *   name = "og:see_also",
 *   group = "open_graph",
 *   weight = 16,
 *   type = "uri",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class OgSeeAlso extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
