<?php

namespace Drupal\metatag_favicons\Plugin\metatag\Tag;

use \Drupal\metatag_favicons\Plugin\metatag\Tag\LinkSizesBase;

/**
 * The Favicons "apple-touch-icon_120x120" meta tag.
 *
 * @MetatagTag(
 *   id = "apple_touch_icon_120x120",
 *   label = @Translation("Apple touch icon: 120px x 120px"),
 *   description = @Translation("A PNG image that is 120px wide by 120px high. Used with iPhone with @2x display running iOS >= 7."),
 *   name = "apple-touch-icon",
 *   group = "favicons",
 *   weight = 11,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AppleTouchIcon120x120 extends LinkSizesBase {
  function sizes() {
    return '120x120';
  }
}

