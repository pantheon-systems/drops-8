<?php

namespace Drupal\metatag_favicons\Plugin\metatag\Tag;

/**
 * The Favicons "apple-touch-icon_76x76" meta tag.
 *
 * @MetatagTag(
 *   id = "apple_touch_icon_76x76",
 *   label = @Translation("Apple touch icon: 76px x 76px"),
 *   description = @Translation("A PNG image that is 76px wide by 76px high. Used with the iPad mini and the second-generation iPad (@1x display) on iOS >= 7."),
 *   name = "apple-touch-icon",
 *   group = "favicons",
 *   weight = 9,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AppleTouchIcon76x76 extends LinkSizesBase {

  /**
   * {@inheritdoc}
   */
  protected function sizes() {
    return '76x76';
  }

}
