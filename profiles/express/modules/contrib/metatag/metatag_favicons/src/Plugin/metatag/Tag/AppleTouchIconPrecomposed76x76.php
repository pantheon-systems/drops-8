<?php

namespace Drupal\metatag_favicons\Plugin\metatag\Tag;

use \Drupal\metatag_favicons\Plugin\metatag\Tag\LinkSizesBase;

/**
 * The Favicons "apple-touch-icon-precomposed_76x76" meta tag.
 *
 * @MetatagTag(
 *   id = "apple_touch_icon_precomposed_76x76",
 *   label = @Translation("Apple touch icon (precomposed): 76px x 76px"),
 *   description = @Translation("A PNG image that is 76px wide by 76px high. Used with the iPad mini and the second-generation iPad (@1x display) on iOS >= 7."),
 *   name = "apple-touch-icon-precomposed",
 *   group = "favicons",
 *   weight = 17,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AppleTouchIconPrecomposed76x76 extends LinkSizesBase {
  function sizes() {
    return '76x76';
  }
}

