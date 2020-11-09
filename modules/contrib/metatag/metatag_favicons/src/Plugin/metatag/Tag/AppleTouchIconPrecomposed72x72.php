<?php

namespace Drupal\metatag_favicons\Plugin\metatag\Tag;

/**
 * The Favicons "apple-touch-icon-precomposed_72x72" meta tag.
 *
 * @MetatagTag(
 *   id = "apple_touch_icon_precomposed_72x72",
 *   label = @Translation("Apple touch icon (precomposed): 72px x 72px"),
 *   description = @Translation("A PNG image that is 72px wide by 72px high. Used with the iPad mini and the first- and second-generation iPad (@1x display) on iOS <= 6."),
 *   name = "apple-touch-icon-precomposed",
 *   group = "favicons",
 *   weight = 16,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AppleTouchIconPrecomposed72x72 extends LinkSizesBase {

  /**
   * {@inheritdoc}
   */
  protected function sizes() {
    return '72x72';
  }

}
