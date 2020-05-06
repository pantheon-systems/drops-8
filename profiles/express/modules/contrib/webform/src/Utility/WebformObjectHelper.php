<?php

namespace Drupal\webform\Utility;

/**
 * Provides helper to operate on objects.
 */
class WebformObjectHelper {

  /**
   * Sort object by properties.
   *
   * @param object $object
   *   An object.
   *
   * @return object
   *   Object sorted by properties.
   */
  public static function sortByProperty($object) {
    $array = (array) $object;
    ksort($array);
    return (object) $array;
  }

}
