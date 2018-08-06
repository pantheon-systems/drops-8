<?php

namespace Drupal\webform\Utility;

/**
 * Provides helper to operate on arrays.
 */
class WebformArrayHelper {

  /**
   * Implode an array with commas separating the elements and with an "and" before the last element.
   *
   * @param array $array
   *   The array to be convert to a string.
   * @param string $conjunction
   *   (optional) The word, which should be 'and' or 'or' used to join the
   *   values of the array. Defaults to 'and'.
   *
   * @return string
   *   The array converted to a string.
   */
  public static function toString(array $array, $conjunction = NULL) {
    if ($conjunction === NULL) {
      $conjunction = t('and');
    }

    switch (count($array)) {
      case 0:
        return '';

      case 1:
        return reset($array);

      case 2:
        return implode(' ' . $conjunction . ' ', $array);

      default:
        $last = array_pop($array);
        return implode(', ', $array) . ', ' . $conjunction . ' ' . $last;
    }
  }

  /**
   * Determine if an array is an associative array.
   *
   * @param array $array
   *   An array.
   *
   * @return bool
   *   TRUE if array is an associative array.
   *
   * @see http://stackoverflow.com/questions/173400/how-to-check-if-php-array-is-associative-or-sequential
   */
  public static function isAssociative(array $array) {
    return array_keys($array) !== range(0, count($array) - 1);
  }

  /**
   * Determine if any values are in an array.
   *
   * @param array $needles
   *   The searched values.
   * @param array $haystack
   *   The array.
   *
   * @return bool
   *   TRUE if any values are in an array.
   *
   * @see http://stackoverflow.com/questions/7542694/in-array-multiple-values
   */
  public static function inArray(array $needles, array $haystack) {
    return !!array_intersect($needles, $haystack);
  }

  /**
   * Determine if an array is a sequential array.
   *
   * @param array $array
   *   An array.
   *
   * @return bool
   *   TRUE if array is a sequential array.
   */
  public static function isSequential(array $array) {
    return !self::isAssociative($array);
  }

  /**
   * Get the first key in an array.
   *
   * @param array $array
   *   An array.
   *
   * @return string|null
   *   The first key in an array.
   */
  public static function getFirstKey(array $array) {
    $keys = array_keys($array);
    return reset($keys);
  }

  /**
   * Get the last key in an array.
   *
   * @param array $array
   *   An array.
   *
   * @return string|null
   *   The last key in an array.
   */
  public static function getLastKey(array $array) {
    $keys = array_keys($array);
    return end($keys);
  }

  /**
   * Get the next key in an array.
   *
   * @param array $array
   *   An array.
   * @param string $key
   *   A key.
   *
   * @return string|null
   *   The next key in an array or NULL is there is no next key.
   */
  public static function getNextKey(array $array, $key) {
    return self::getKey($array, $key, 'next');
  }

  /**
   * Get the prev(ious) key in an array.
   *
   * @param array $array
   *   An array.
   * @param string $key
   *   A key.
   *
   * @return string|null
   *   The prev(ious) key in an array or NULL is there is no previous key.
   */
  public static function getPreviousKey(array $array, $key) {
    return self::getKey($array, $key, 'prev');
  }

  /**
   * Get next or prev(ious) array key.
   *
   * @param array $array
   *   An array.
   * @param string $key
   *   A array key.
   * @param string $direction
   *   The direction of the  key to retrieve.
   *
   * @return string|null
   *   The next or prev(ious) array key or NULL if no key is found.
   *
   * @see http://stackoverflow.com/questions/6407795/get-the-next-array-item-using-the-key-php
   */
  protected static function getKey(array $array, $key, $direction) {
    $array_keys = array_keys($array);
    $array_key = reset($array_keys);
    do {
      if ($array_key == $key) {
        return $direction($array_keys);
      }
    } while ($array_key = next($array_keys));
    return NULL;
  }

  /**
   * Add prefix to all top level keys in an associative array.
   *
   * @param array $array
   *   An associative array.
   * @param string $prefix
   *   Prefix to be prepended to all keys.
   *
   * @return array
   *   An associative array with all top level keys prefixed.
   */
  public static function addPrefix(array $array, $prefix = '#') {
    $prefixed_array = [];
    foreach ($array as $key => $value) {
      if ($key[0] != $prefix) {
        $key = $prefix . $key;
      }
      $prefixed_array[$key] = $value;
    }
    return $prefixed_array;
  }

  /**
   * Remove prefix from all top level keys in an associative array.
   *
   * @param array $array
   *   An associative array.
   * @param string $prefix
   *   Prefix to be remove from to all keys.
   *
   * @return array
   *   An associative array with prefix removed from all top level keys.
   */
  public static function removePrefix(array $array, $prefix = '#') {
    $unprefixed_array = [];
    foreach ($array as $key => $value) {
      if ($key[0] == $prefix) {
        $key = preg_replace('/^' . $prefix . '/', '', $key);
      }
      $unprefixed_array[$key] = $value;
    }
    return $unprefixed_array;
  }

  /**
   * Shuffle an associative array while maintaining keys.
   *
   * @param array $array
   *   An associative array.
   *
   * @return array
   *   The associative array with it key/value pairs randomized.
   *
   * @see http://stackoverflow.com/questions/4102777/php-random-shuffle-array-maintaining-key-value
   */
  public static function shuffle(array $array) {
    $keys = array_keys($array);
    shuffle($keys);
    $random = [];
    foreach ($keys as $key) {
      $random[$key] = $array[$key];
    }
    return $random;
  }

  /**
   * Checks if multiple keys exist in an array.
   *
   * @param array $array
   *   An associative array.
   * @param array $keys
   *   Keys.
   *
   * @return bool
   *  TRUE if multiple keys exist in an array.
   *
   * @see https://wpscholar.com/blog/check-multiple-array-keys-exist-php/.
   */
  public static function keysExist(array $array, array $keys) {
    $count = 0;
    foreach ($keys as $key) {
      if (array_key_exists($key, $array)) {
        $count ++;
      }
    }

    return count($keys) === $count;
  }

}
