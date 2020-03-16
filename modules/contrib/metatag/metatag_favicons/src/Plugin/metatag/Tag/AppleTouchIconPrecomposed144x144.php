<?php

namespace Drupal\metatag_favicons\Plugin\metatag\Tag;

/**
 * The Favicons "apple-touch-icon-precomposed_144x144" meta tag.
 *
 * @MetatagTag(
 *   id = "apple_touch_icon_precomposed_144x144",
 *   label = @Translation("Apple touch icon (precomposed): 144px x 144px"),
 *   description = @Translation("A PNG image that is 144px wide by 144px high. Used with iPad with @2x display running iOS <= 6."),
 *   name = "apple-touch-icon-precomposed",
 *   group = "favicons",
 *   weight = 20,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AppleTouchIconPrecomposed144x144 extends LinkSizesBase {

  /**
   * {@inheritdoc}
   */
  protected function sizes() {
    return '144x144';
  }

}
