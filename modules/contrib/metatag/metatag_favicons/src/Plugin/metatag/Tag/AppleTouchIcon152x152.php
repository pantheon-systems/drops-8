<?php

namespace Drupal\metatag_favicons\Plugin\metatag\Tag;

/**
 * The Favicons "apple-touch-icon_152x152" meta tag.
 *
 * @MetatagTag(
 *   id = "apple_touch_icon_152x152",
 *   label = @Translation("Apple touch icon: 152px x 152px"),
 *   description = @Translation("A PNG image that is 152px wide by 152px high. Used with iPad with @2x display running iOS >= 7."),
 *   name = "apple-touch-icon",
 *   group = "favicons",
 *   weight = 13,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AppleTouchIcon152x152 extends LinkSizesBase {

  /**
   * {@inheritdoc}
   */
  protected function sizes() {
    return '152x152';
  }

}
