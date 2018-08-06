<?php

/**
 * @file
 * Generate examples of all elements using HTML markup.
 */

module_load_include('inc', 'webform_test', 'includes/webform_test.example_elements');

/**
 * Generate examples of all elements using HTML markup.
 *
 * @return array
 *   An array containing examples of all elements using HTML markup.
 */
function webform_test_test_element_html_markup() {
  $elements = webform_test_example_elements();
  _webform_test_test_element_html_markup_recursive($elements);
  return $elements;
}

/**
 * Add HTML markup to elements.
 *
 * @param array $elements
 *   An array of elements.
 */
function _webform_test_test_element_html_markup_recursive(array &$elements) {
  foreach ($elements as $key => &$property) {
    if (is_string($property) && is_string($key) && in_array($key, ['#title', '#field_prefix', '#field_suffix'])) {
      $property = '<u>' . $property . '</u>';
    }

    if (is_array($property)) {
      _webform_test_test_element_html_markup_recursive($property);
    }
  }
}
