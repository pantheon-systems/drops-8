<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:fax_number' meta tag.
 *
 * @MetatagTag(
 *   id = "og_fax_number",
 *   label = @Translation("Fax number"),
 *   description = @Translation(""),
 *   name = "og:fax_number",
 *   group = "open_graph",
 *   weight = 25,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class OgFaxNumber extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
