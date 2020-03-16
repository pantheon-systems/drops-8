<?php

namespace Drupal\metatag_facebook\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * The Facebook "fb:pages" meta tag.
 *
 * @MetatagTag(
 *   id = "fb_pages",
 *   label = @Translation("Facebook Pages"),
 *   description = @Translation("Facebook Instant Articles claim URL token."),
 *   name = "fb:pages",
 *   group = "facebook",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class FbPages extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
