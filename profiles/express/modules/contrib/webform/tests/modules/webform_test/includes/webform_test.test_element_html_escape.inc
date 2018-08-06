<?php

/**
 * @file
 * Generate examples of all elements using HTML escape.
 */

module_load_include('inc', 'webform_test', 'includes/webform_test.test_example_elements');

/**
 * Generate examples of all elements using HTML escape.
 *
 * @return array
 *   An array containing examples of all elements using HTML escaping.
 */
function webform_test_test_element_html_escape() {
  $elements = webform_test_test_example_elements();
  _webform_test_test_element_html_escape_recursive($elements);
  return $elements;
}

/**
 * Add script tag to elements.
 *
 * @param array $elements
 *   An array of elements.
 */
function _webform_test_test_element_html_escape_recursive(array &$elements) {
  foreach ($elements as $key => &$property) {
    if (is_string($property) && is_string($key) && in_array($key, ['#title', '#field_prefix', '#field_suffix'])) {
      $property .= ' | <script>alert(\'This markup is not escaped properly!!!\') </script>';
    }

    if (is_array($property)) {
      _webform_test_test_element_html_escape_recursive($property);
    }
  }
}
