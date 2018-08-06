<?php

namespace Drupal\metatag_favicons\Plugin\metatag\Tag;

use \Drupal\metatag_favicons\Plugin\metatag\Tag\LinkSizesBase;

/**
 * The Favicons "apple-touch-icon_144x144" meta tag.
 *
 * @MetatagTag(
 *   id = "apple_touch_icon_144x144",
 *   label = @Translation("Apple touch icon: 144px x 144px"),
 *   description = @Translation("A PNG image that is 144px wide by 144px high. Used with iPad with @2x display running iOS <= 6."),
 *   name = "apple-touch-icon",
 *   group = "favicons",
 *   weight = 12,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AppleTouchIcon144x144 extends LinkSizesBase {
  function sizes() {
    return '144x144';
  }
}

