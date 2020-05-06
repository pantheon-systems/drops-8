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
   *   The next key in an array or NULL if there is no next key.
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
   *   The prev(ious) key in an array or NULL if there is no previous key.
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
   *   The direction of the key to retrieve.
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
   *   TRUE if multiple keys exist in an array.
   *
   * @see https://wpscholar.com/blog/check-multiple-array-keys-exist-php/
   */
  public static function keysExist(array $array, array $keys) {
    $count = 0;
    foreach ($keys as $key) {
      if (array_key_exists($key, $array)) {
        $count++;
      }
    }

    return count($keys) === $count;
  }

  /**
   * Get duplicate values in an array.
   *
   * @param array $array
   *   An array.
   *
   * @return array
   *   An array container duplicate values.
   *
   * @see https://magp.ie/2011/02/02/find-duplicates-in-an-array-with-php/
   */
  public static function getDuplicates(array $array) {
    return array_unique(array_diff_assoc($array, array_unique($array)));
  }

  /**
   * Traverse an associative array and collect references to all key/value pairs in an associative array.
   *
   * @param array $build
   *   An array.
   *
   * @return array
   *   An associative array of key/value pairs by reference.
   */
  public static function &flattenAssoc(array &$build) {
    $array = [];
    $duplicate_array_keys = [];
    self::flattenAssocRecursive($build, $array, $duplicate_array_keys);
    return $array;
  }

  /**
   * TTraverse an associative array and collect references to all key/value pairs in an associative array.
   *
   * @param array $build
   *   An array.
   * @param array $array
   *   An empty array that will be populated with references to key/value pairs.
   * @param array $duplicate_array_keys
   *   An array used to track key/value pairs with duplicate keys.
   */
  protected static function flattenAssocRecursive(array &$build, array &$array, array &$duplicate_array_keys) {
    foreach ($build as $key => &$item) {
      // If there are duplicate array keys create an array of referenced
      // key/value pairs.
      if (isset($array[$key])) {
        // If this is the second key/value pairs, we need to restructure to
        // first key/value's reference to be an array of references.
        if (empty($duplicate_array_keys[$key])) {
          // Store a temporary references to the first key/value pair.
          $first_element = &$array[$key];
          // Use unset() to break the reference.
          unset($array[$key]);
          // Create an array of element references.
          $array[$key] = [];
          // Append the first to the array of key/value references.
          $array[$key][] = &$first_element;
        }
        // Now append the current key/value pair to array of key/value pair
        // references.
        $array[$key][] = &$build[$key];
        // Finally track key/value pairs with duplicate keys.
        $duplicate_array_keys[$key] = TRUE;
      }
      else {
        $array[$key] = &$build[$key];
      }

      if (is_array($item)) {
        self::flattenAssocRecursive($item, $array, $duplicate_array_keys);
      }
    }
  }

  /**
   * Inserts a new key/value before the key in the array.
   *
   * @param array &$array
   *   An array to insert in to.
   * @param string $target_key
   *   The key to insert before.
   * @param string $new_key
   *   The key to insert.
   * @param mixed $new_value
   *   An value to insert.
   */
  public static function insertBefore(array &$array, $target_key, $new_key, $new_value) {
    $new = [];
    foreach ($array as $k => $value) {
      if ($k === $target_key) {
        $new[$new_key] = $new_value;
      }
      $new[$k] = $value;
    }
    $array = $new;
  }

  /**
   * Inserts a new key/value after the key in the array.
   *
   * @param array &$array
   *   An array to insert in to.
   * @param string $target_key
   *   The key to insert after.
   * @param string $new_key
   *   The key to insert.
   * @param mixed $new_value
   *   An value to insert.
   */
  public static function insertAfter(array &$array, $target_key, $new_key, $new_value) {
    $new = [];
    foreach ($array as $key => $value) {
      $new[$key] = $value;
      if ($key === $target_key) {
        $new[$new_key] = $new_value;
      }
    }
    $array = $new;
  }

  /**
   * Remove value from an array.
   *
   * @param array &$array
   *   An array.
   * @param mixed $value
   *   A value.
   *
   * @see https://stackoverflow.com/questions/7225070/php-array-delete-by-value-not-key
   */
  public static function removeValue(array &$array, $value) {
    if (($key = array_search($value, $array)) !== FALSE) {
      unset($array[$key]);
    }
    if (static::isSequential($array)) {
      array_values($array);
    }
  }

}
