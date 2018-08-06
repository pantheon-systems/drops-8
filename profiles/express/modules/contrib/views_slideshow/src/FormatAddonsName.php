<?php

namespace Drupal\views_slideshow;

/**
 * Provides a class to manipulate addons names.
 */
class FormatAddonsName implements FormatAddonsNameInterface {

  /**
   * Format callback to move from underscore separated words to camelCase.
   */
  public function format($subject) {
    return preg_replace_callback('/_(.?)/', function ($matches) {
      if (isset($matches[1])) {
        return strtoupper($matches[1]);
      }
    }, $subject);
  }

}
