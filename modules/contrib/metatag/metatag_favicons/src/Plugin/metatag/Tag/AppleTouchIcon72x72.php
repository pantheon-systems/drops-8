<?php

namespace Drupal\metatag_favicons\Plugin\metatag\Tag;

/**
 * The Favicons "icon_16x16" meta tag.
 *
 * @MetatagTag(
 *   id = "apple_touch_icon_72x72",
 *   label = @Translation("Apple touch icon: 72px x 72px"),
 *   description = @Translation("A PNG image that is 72px wide by 72px high. Used with the iPad mini and the first- and second-generation iPad (@1x display) on iOS <= 6."),
 *   name = "apple-touch-icon",
 *   group = "favicons",
 *   weight = 8,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AppleTouchIcon72x72 extends LinkSizesBase {

  /**
   * {@inheritdoc}
   */
  protected function sizes() {
    return '72x72';
  }

}
