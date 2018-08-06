<?php

/**
 * @file
 * Generate test elements with Flexbox flex ratios.
 */

/**
 * Generate test elements with Flexbox flex ratios.
 *
 * @return array
 *   An array containing test elements with Flexbox flex ratios.
 */
function webform_test_test_element_flexbox_flex() {
  $grid = [
    [1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1],
    [1, 1],
    [1, 1, 1],
    [1, 1, 1, 1],
    [1, 1, 1, 1, 1, 1],
    [1, 1, 1, 1, 1, 1, 1, 1],
    [1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1],
    [2, 10],
    [3, 9],
    [4, 8],
    [5, 7],
    [6, 6],
    [7, 5],
    [8, 4],
    [9, 3],
    [10, 2],
    [1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1],
    [1, 10, 1],
    [2, 8, 2],
    [3, 6, 3],
    [4, 4, 4],
    [5, 2, 5],
    [6, 6],
    [1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1],
  ];
  $elements = [];
  foreach ($grid as $row_index => $row) {
    $elements["row_$row_index"] = [
      '#type' => 'webform_flexbox',
    ];
    foreach ($row as $col_index => $flex) {
      $elements["row_$row_index"]["row_{$row_index}_col_{$col_index}"] = [
        '#type' => 'container',
        '#flex' => $flex,
        '#attributes' => [
          'style' => 'padding: 30px; background: #ccc; text-align: center',
        ],
        'flex' => ['#markup' => $flex],
      ];
    }
  }
  return $elements;
}
