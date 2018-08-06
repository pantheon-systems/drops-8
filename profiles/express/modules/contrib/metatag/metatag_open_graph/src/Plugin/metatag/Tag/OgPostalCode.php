<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:postal_code' meta tag.
 *
 * @MetatagTag(
 *   id = "og_postal_code",
 *   label = @Translation("Postal/ZIP code"),
 *   description = @Translation(""),
 *   name = "og:postal_code",
 *   group = "open_graph",
 *   weight = 21,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class OgPostalCode extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
