<?php

/**
 * @file
 * Generate test options example.
 */

use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Entity\WebformOptions;

/**
 * Generate test options.
 *
 * @return array
 *   An array containing test options.
 */
function webform_test_test_options() {
  $data = [
    'general_options' => [
      '#type' => 'details',
      '#title' => 'General options',
      '#open' => TRUE,
    ],
    'bio_options' => [
      '#type' => 'details',
      '#title' => 'Biographical options',
      '#open' => TRUE,
    ],
    'location_options' => [
      '#type' => 'details',
      '#title' => 'Location options',
      '#open' => TRUE,
    ],
    'date_options' => [
      '#type' => 'details',
      '#title' => 'Date options',
      '#open' => TRUE,
    ],
    'likert_options' => [
      '#type' => 'details',
      '#title' => 'Likert options',
      '#open' => TRUE,
    ],
    'test_options' => [
      '#type' => 'details',
      '#title' => 'Test options',
      '#open' => TRUE,
    ],
  ];
  $webform_options = WebformOptions::loadMultiple();
  ksort($webform_options);
  foreach ($webform_options as $id => $webform_option) {
    $title = $webform_option->label() . ' (' . $id . ')';
    if (strpos($id, 'likert') === 0) {
      $data['likert_options'][$id] = [
        '#type' => 'webform_likert',
        '#title' => $title,
        '#questions' => [
          'q1' => 'Please answer question 1?',
          'q2' => 'How about now answering question 2?',
          'q3' => 'Finally, here is question 3?',
        ],
        '#answers' => $id,
      ];
    }
    else {
      if (preg_match('/(state|country|countries)/', $id)) {
        $group = 'location_options';
      }
      elseif (preg_match('/(months|days)/', $id)) {
        $group = 'date_options';
      }
      elseif (preg_match('/(time_zones|creditcard|yes_no|days|size)/', $id)) {
        $group = 'general_options';
      }
      elseif ($id == 'test') {
        $group = 'test_options';
      }
      else {
        $group = 'bio_options';
      }

      $data[$group][$id] = [
        '#type' => 'select',
        '#title' => $title,
        '#options' => $id,
      ];

    }
  }

  $data['test_options']['custom'] = [
    '#type' => 'select',
    '#title' => 'Custom (custom)',
    '#options' => 'custom',
  ];

  $yaml = file_get_contents(drupal_get_path('module', 'webform_test') . '/includes/webform_test.test_options.yml');
  $default_elements = Yaml::decode($yaml);

  return $data + $default_elements;
}
