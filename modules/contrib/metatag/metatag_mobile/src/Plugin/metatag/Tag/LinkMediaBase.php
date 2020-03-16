<?php

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\LinkRelBase;

/**
 * This base plugin allows "link rel" tags with a "media" attribute.
 */
abstract class LinkMediaBase extends LinkRelBase {

  /**
   * {@inheritdoc}
   */
  public function output() {
    $element = parent::output();

    if ($element) {
      $element['#attributes'] = [
        'rel' => $this->name(),
        'media' => $this->media(),
        'href' => $element['#attributes']['href'],
      ];
    }

    return $element;
  }

  /**
   * The dimensions supported by this icon.
   *
   * @return string
   *   A string for the desired media type.
   */
  protected function media() {
    return '';
  }

}
