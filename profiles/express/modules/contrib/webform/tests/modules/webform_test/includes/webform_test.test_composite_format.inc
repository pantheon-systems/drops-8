<?php

/**
 * @file
 * Generate test composite elements with formatting.
 */

use Drupal\webform\WebformInterface;

module_load_include('inc', 'webform_test', 'includes/webform_test.test_element_format');

/**
 * Generate test composite element formats.
 *
 * @return array
 *   An array containing test elements formats.
 */
function webform_test_test_composite_format(WebformInterface $webform) {
  return webform_test_test_element_format($webform, TRUE, FALSE);
}
