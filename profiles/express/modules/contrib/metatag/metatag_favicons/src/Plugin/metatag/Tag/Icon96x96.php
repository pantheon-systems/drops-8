<?php

namespace Drupal\metatag_favicons\Plugin\metatag\Tag;

use \Drupal\metatag_favicons\Plugin\metatag\Tag\LinkSizesBase;

/**
 * The Favicons "icon_96x96" meta tag.
 *
 * @MetatagTag(
 *   id = "icon_96x96",
 *   label = @Translation("Icon: 96px x 96px"),
 *   description = @Translation("A PNG image that is 96px wide by 96px high."),
 *   name = "icon",
 *   group = "favicons",
 *   weight = 5,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Icon96x96 extends LinkSizesBase {
  function sizes() {
    return '96x96';
  }
}

