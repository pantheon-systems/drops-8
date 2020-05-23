<?php

namespace Drupal\Driver\Fields\Drupal7;

/**
 * Link field handler for Drupal 7.
 */
class LinkFieldHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $return = [];
    foreach ($values as $value) {
      $return[$this->language][] = [
        'title' => $value[0],
        'url' => $value[1],
      ];
    }
    return $return;
  }

}
