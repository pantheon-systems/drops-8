<?php

namespace Drupal\metatag_favicons\Plugin\metatag\Tag;

/**
 * The Favicons "icon_192x192" meta tag.
 *
 * @MetatagTag(
 *   id = "icon_192x192",
 *   label = @Translation("Icon: 192px x 192px"),
 *   description = @Translation("A PNG image that is 192px wide by 192px high."),
 *   name = "icon",
 *   group = "favicons",
 *   weight = 6,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Icon192x192 extends LinkSizesBase {

  /**
   * {@inheritdoc}
   */
  protected function sizes() {
    return '192x192';
  }

}
