<?php

namespace Drupal\metatag_favicons\Plugin\metatag\Tag;

use \Drupal\metatag_favicons\Plugin\metatag\Tag\LinkSizesBase;

/**
 * The Favicons "apple-touch-icon-precomposed_114x114" meta tag.
 *
 * @MetatagTag(
 *   id = "apple_touch_icon_precomposed_114x114",
 *   label = @Translation("Apple touch icon (precomposed): 114px x 114px"),
 *   description = @Translation("A PNG image that is 114px wide by 114px high. Used with iPhone with @2x display running iOS <= 6."),
 *   name = "apple-touch-icon-precomposed",
 *   group = "favicons",
 *   weight = 18,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AppleTouchIconPrecomposed114x114 extends LinkSizesBase {
  function sizes() {
    return '114x114';
  }
}

