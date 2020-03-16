<?php

namespace Drupal\metatag_favicons\Plugin\metatag\Tag;

/**
 * The Favicons "apple-touch-icon_114x114" meta tag.
 *
 * @MetatagTag(
 *   id = "apple_touch_icon_114x114",
 *   label = @Translation("Apple touch icon: 114px x 114px"),
 *   description = @Translation("A PNG image that is 114px wide by 114px high. Used with iPhone with @2x display running iOS <= 6."),
 *   name = "apple-touch-icon",
 *   group = "favicons",
 *   weight = 10,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AppleTouchIcon114x114 extends LinkSizesBase {

  /**
   * {@inheritdoc}
   */
  protected function sizes() {
    return '114x114';
  }

}
