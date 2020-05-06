<?php

/**
 * @file
 * Generate long webform elements.
 */

use Drupal\webform\WebformInterface;

/**
 * Generate long webform elements.
 *
 * @param \Drupal\webform\WebformInterface $webform
 *   A webform object.
 *
 * @return array
 *   An array containing long webform elements.
 */
function webform_test_test_form_long(WebformInterface $webform) {
  $length = (int) str_replace('test_form_long_', '', $webform->id());
  $elements = [];
  for ($i = 1; $i <= $length; $i++) {
    $elements["element_$i"] = [
      '#type' => 'textfield',
      '#title' => (string) t('Element #@index', ['@index' => $i]),
    ];
  }
  return $elements;
}
