<?php

namespace Drupal\metatag_favicons\Plugin\metatag\Tag;

use \Drupal\metatag_favicons\Plugin\metatag\Tag\LinkSizesBase;

/**
 * The Favicons "apple-touch-icon-precomposed_152x152" meta tag.
 *
 * @MetatagTag(
 *   id = "apple_touch_icon_precomposed_152x152",
 *   label = @Translation("Apple touch icon (precomposed): 152px x 152px"),
 *   description = @Translation("A PNG image that is 152px wide by 152px high. Used with iPad with @2x display running iOS >= 7."),
 *   name = "apple-touch-icon-precomposed",
 *   group = "favicons",
 *   weight = 21,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AppleTouchIconPrecomposed152x152 extends LinkSizesBase {
  function sizes() {
    return '152x152';
  }
}

