<?php

namespace Drupal\metatag_favicons\Plugin\metatag\Tag;

/**
 * The Favicons "icon_16x16" meta tag.
 *
 * @MetatagTag(
 *   id = "icon_16x16",
 *   label = @Translation("Icon: 16px x 16px"),
 *   description = @Translation("A PNG image that is 16px wide by 16px high."),
 *   name = "icon",
 *   group = "favicons",
 *   weight = 3,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   sizes = "16x16"
 * )
 */
class Icon16x16 extends LinkSizesBase {

  /**
   * {@inheritdoc}
   */
  protected function sizes() {
    return '16x16';
  }

}
