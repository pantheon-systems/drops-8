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
function webform_test_test_form_wizard_long(WebformInterface $webform) {
  $wizard_index = 1;
  $length = (int) str_replace('test_form_wizard_long_', '', $webform->id());
  $elements = [];
  for ($i = 1; $i <= $length; $i++) {
    if (!isset($elements["wizard_$wizard_index"])) {
      $elements["wizard_$wizard_index"] = [
        '#type' => 'webform_wizard_page',
        '#title' => (string) t('Wizard page #@index', ['@index' => $wizard_index]),
      ];
    }

    $elements["wizard_$wizard_index"]["element_$i"] = [
      '#type' => 'textfield',
      '#title' => (string) t('Element #@index', ['@index' => $i]),
    ];

    if ($i % 10 === 0) {
      $wizard_index++;
    }
  }

  return $elements;
}
