<?php

namespace Drupal\webform\Utility;

use Drupal\Core\Render\Element;

/**
 * Helper class webform based methods.
 */
class WebformFormHelper {

  /**
   * Cleanup webform state values.
   *
   * @param array $values
   *   An array of webform state values.
   * @param array $keys
   *   (optional) An array of custom keys to be removed.
   *
   * @return array
   *   The values without default keys like
   *   'form_build_id', 'form_token', 'form_id', 'op', 'actions', etc...
   */
  public static function cleanupFormStateValues(array $values, array $keys = []) {
    // Remove default FAPI values.
    unset(
      $values['form_build_id'],
      $values['form_token'],
      $values['form_id'],
      $values['op']
    );

    // Remove any objects.
    foreach ($values as $key => $value) {
      if (is_object($value)) {
        unset($values[$key]);
      }
    }

    // Remove custom keys.
    foreach ($keys as $key) {
      unset($values[$key]);
    }
    return $values;
  }

  /**
   * Traverse a render array and collect references to all elements in an associative array keyed by element name.
   *
   * @param array $build
   *   An render array.
   *
   * @return array
   *   An associative array of elements by reference.
   */
  public static function &flattenElements(array &$build) {
    $elements = [];
    $duplicate_element_keys = [];
    static::flattenElementsRecursive($build, $elements, $duplicate_element_keys);
    return $elements;
  }

  /**
   * Traverse a render array and collect references to all elements in an associative array keyed by element name.
   *
   * @param array $build
   *   An render array.
   * @param array $elements
   *   An empty array that  will be populated with references to all elements.
   * @param array $duplicate_element_keys
   *   An array used to track elements with duplicate keys.
   */
  protected static function flattenElementsRecursive(array &$build, array &$elements, array &$duplicate_element_keys) {
    foreach ($build as $key => &$element) {
      if (Element::child($key) && is_array($element)) {
        // If there are duplicate element keys create an array of referenced
        // elements.
        if (isset($elements[$key])) {
          // If this is the second element, we need to restructure to first
          // element's reference to be an array of references.
          if (empty($duplicate_element_keys[$key])) {
            // Store a temporary references to the first element.
            $first_element =& $elements[$key];
            // Use unset() to break the reference.
            unset($elements[$key]);
            // Create an array of element references.
            $elements[$key] = [];
            // Append the first to the array of element references.
            $elements[$key][] =& $first_element;
          }
          // Now append the current element to array of element references.
          $elements[$key][] =& $build[$key];
          // Finally †rack elements with duplicate keys.
          $duplicate_element_keys[$key] = TRUE;
        }
        else {
          $elements[$key] =& $build[$key];
        }

        static::flattenElementsRecursive($element, $elements, $duplicate_element_keys);
      }
    }
  }

}
