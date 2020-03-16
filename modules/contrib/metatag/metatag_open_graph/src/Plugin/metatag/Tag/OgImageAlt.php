<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:image:alt' meta tag.
 *
 * @MetatagTag(
 *   id = "og_image_alt",
 *   label = @Translation("Image 'alt'"),
 *   description = @Translation("A description of what is in the image, not a caption. If the page specifies an og:image it should specify og:image:alt."),
 *   name = "og:image:alt",
 *   group = "open_graph",
 *   weight = 15,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   absolute_url = FALSE
 * )
 */
class OgImageAlt extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
