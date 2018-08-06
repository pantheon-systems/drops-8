<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:phone_number' meta tag.
 *
 * @MetatagTag(
 *   id = "og_phone_number",
 *   label = @Translation("Phone number"),
 *   description = @Translation(""),
 *   name = "og:phone_number",
 *   group = "open_graph",
 *   weight = 24,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class OgPhoneNumber extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
