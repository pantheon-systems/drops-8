<?php

namespace Drupal\metatag_favicons\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\LinkRelBase;

/**
 * This base plugin allows "link rel" tags with a "sizes" attribute.
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
   * The dimensions supported by this icon.
   *
   * @return string
   *   A string in the format "XxY" for a given width and height.
   */
  protected function sizes() {
    return '';
  }

}
