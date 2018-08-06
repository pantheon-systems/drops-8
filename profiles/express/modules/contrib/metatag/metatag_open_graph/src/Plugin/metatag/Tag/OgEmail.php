<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:email' meta tag.
 *
 * @MetatagTag(
 *   id = "og_email",
 *   label = @Translation("Email address"),
 *   description = @Translation(""),
 *   name = "og:email",
 *   group = "open_graph",
 *   weight = 23,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class OgEmail extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
