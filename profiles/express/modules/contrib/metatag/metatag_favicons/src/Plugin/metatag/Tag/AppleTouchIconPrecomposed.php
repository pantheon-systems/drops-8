<?php

namespace Drupal\metatag_favicons\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\LinkRelBase;

/**
 * The Favicons "apple-touch-icon-precomposed" meta tag.
 *
 * @MetatagTag(
 *   id = "apple_touch_icon_precomposed",
 *   label = @Translation("Apple touch icon (precomposed): 57px x 57px"),
 *   description = @Translation("A PNG image that is 57px wide by 57px high. Used with the non-Retina iPhone, iPod Touch, and Android 2.1+ devices."),
 *   name = "apple-touch-icon-precomposed",
 *   group = "favicons",
 *   weight = 15,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AppleTouchIconPrecomposed extends LinkRelBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}

