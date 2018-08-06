<?php

namespace Drupal\metatag_favicons\Plugin\metatag\Tag;

use \Drupal\metatag_favicons\Plugin\metatag\Tag\LinkSizesBase;

/**
 * The Favicons "apple-touch-icon_180x180" meta tag.
 *
 * @MetatagTag(
 *   id = "apple_touch_icon_180x180",
 *   label = @Translation("Apple touch icon: 180px x 180px"),
 *   description = @Translation("A PNG image that is 180px wide by 180px high. Used with iPhone 6 Plus with @3x display."),
 *   name = "apple-touch-icon",
 *   group = "favicons",
 *   weight = 14,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AppleTouchIcon180x180 extends LinkSizesBase {
  function sizes() {
    return '180x180';
  }
}

