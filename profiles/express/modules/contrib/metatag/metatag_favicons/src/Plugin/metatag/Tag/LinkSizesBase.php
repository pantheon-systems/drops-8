<?php

namespace Drupal\metatag_favicons\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\LinkRelBase;

/**
 * This base plugin allows "link hreflang" tags to be further customized.
 */
abstract class LinkSizesBase extends LinkRelBase {

  /**
   * {@inheritdoc}
   */
  public function output() {
    $element = parent::output();

    if ($element) {
      $element['#attributes'] = [
        'rel' => $this->name(),
        'sizes' => $this->sizes(),
        'href' => $element['#attributes']['href'],
        ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  //public function sizes() {
  //  return parent::sizes();
  //}

}
