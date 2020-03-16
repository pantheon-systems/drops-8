<?php

namespace Drupal\metatag_hreflang\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\LinkRelBase;

/**
 * This base plugin allows "link hreflang" tags to be further customized.
 */
abstract class HreflangBase extends LinkRelBase {

  /**
   * {@inheritdoc}
   */
  public function output() {
    $element = parent::output();

    if ($element) {
      // Rewrite the attributes so the hreflang value is before the href value.
      $element['#attributes'] = [
        'rel' => 'alternate',
        'hreflang' => $this->name(),
        'href' => $element['#attributes']['href'],
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function name() {
    return str_replace('hreflang_', '', parent::name());
  }

}
