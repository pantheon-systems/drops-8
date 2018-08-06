<?php

namespace Drupal\metatag_favicons\Plugin\metatag\Tag;

use \Drupal\metatag_favicons\Plugin\metatag\Tag\LinkSizesBase;

/**
 * The Favicons "icon_32x32" meta tag.
 *
 * @MetatagTag(
 *   id = "icon_32x32",
 *   label = @Translation("Icon: 32px x 32px"),
 *   description = @Translation("A PNG image that is 32px wide by 32px high."),
 *   name = "icon",
 *   group = "favicons",
 *   weight = 4,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Icon32x32 extends LinkSizesBase {
  function sizes() {
    return '32x32';
  }
}

