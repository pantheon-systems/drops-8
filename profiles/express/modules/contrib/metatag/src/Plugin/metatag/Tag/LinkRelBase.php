<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * This base plugin allows "link rel" tags to be further customized.
 */
abstract class LinkRelBase extends MetaNameBase {

  /**
   * {@inheritDoc}
   */
  public function output() {
    $element = parent::output();
    if (!empty($element['#attributes']['content'])) {
      $element['#tag'] = 'link';
      $element['#attributes'] = [
        'rel' => $this->name(),
        'href' => $element['#attributes']['content'],
      ];
      unset($element['#attributes']['content']);
    }

    return $element;
  }

}
