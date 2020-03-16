<?php

namespace Drupal\metatag_favicons\Plugin\metatag\Tag;

/**
 * The Favicons "apple-touch-icon-precomposed_180x180" meta tag.
 *
 * @MetatagTag(
 *   id = "apple_touch_icon_precomposed_180x180",
 *   label = @Translation("Apple touch icon (precomposed): 180px x 180px"),
 *   description = @Translation("A PNG image that is 180px wide by 180px high. Used with iPhone 6 Plus with @3x display."),
 *   name = "apple-touch-icon-precomposed",
 *   group = "favicons",
 *   weight = 22,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AppleTouchIconPrecomposed180x180 extends LinkSizesBase {

  /**
   * {@inheritdoc}
   */
  protected function sizes() {
    return '180x180';
  }

}
