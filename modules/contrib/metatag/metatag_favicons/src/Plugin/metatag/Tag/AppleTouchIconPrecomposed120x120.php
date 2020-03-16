<?php

namespace Drupal\metatag_favicons\Plugin\metatag\Tag;

/**
 * The Favicons "apple-touch-icon-precomposed_120x120" meta tag.
 *
 * @MetatagTag(
 *   id = "apple_touch_icon_precomposed_120x120",
 *   label = @Translation("Apple touch icon (precomposed): 120px x 120px"),
 *   description = @Translation("A PNG image that is 120px wide by 120px high. Used with iPhone with @2x display running iOS >= 7."),
 *   name = "apple-touch-icon-precomposed",
 *   group = "favicons",
 *   weight = 19,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AppleTouchIconPrecomposed120x120 extends LinkSizesBase {

  /**
   * {@inheritdoc}
   */
  protected function sizes() {
    return '120x120';
  }

}
