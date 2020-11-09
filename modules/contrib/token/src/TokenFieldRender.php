<?php

namespace Drupal\token;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Render\Element;

class TokenFieldRender implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRender'];
  }

  /**
   * Pre-render callback for field output used with tokens.
   */
  public static function preRender($elements) {
    // Remove the field theme hook, attachments, and JavaScript states.
    unset($elements['#theme']);
    unset($elements['#states']);
    unset($elements['#attached']);

    // Prevent multi-value fields from appearing smooshed together by appending
    // a join suffix to all but the last value.
    $deltas = Element::getVisibleChildren($elements);
    $count = count($deltas);
    if ($count > 1) {
      $join = isset($elements['#token_options']['join']) ? $elements['#token_options']['join'] : ", ";
      foreach ($deltas as $index => $delta) {
        // Do not add a suffix to the last item.
        if ($index < ($count - 1)) {
          $elements[$delta] += ['#suffix' => $join];
        }
      }
    }
    return $elements;
  }

}
