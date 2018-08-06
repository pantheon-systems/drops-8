<?php

/**
 * @file
 * Generate test element icheck styles.
 */

/**
 * Generate test elements with #icheck style.
 *
 * @return array
 *   An array containing test elements with #icheck style.
 */
function webform_test_test_element_icheck_styles() {
  $elements = [];

  $skins = [
    'minimal' => t('Minimal'),
    'flat' => t('Flat'),
    'line' => t('Line'),
    'square' => t('Square'),
  ];
  $colors = [
    '' => t('Black'),
    'grey' => t('Grey'),
    'yellow' => t('Yellow'),
    'orange' => t('Orange'),
    'red' => t('Red'),
    'pink' => t('Pink'),
    'purple' => t('Purple'),
    'blue' => t('Blue'),
    'green' => t('Green'),
    'aero' => t('Aero'),
  ];
  foreach ($skins as $skin_name => $skin_title) {
    $skin_title = (string) $skin_title;
    $elements[$skin_name] = [
      '#type' => 'details',
      '#title' => $skin_title,
      '#open' => TRUE,
    ];
    foreach ($colors as $color_name => $color_title) {
      if ($color_name) {
        $name = $skin_name . '_' . $color_name;
        $title = "$color_title";
        $style = "$skin_name-$color_name";
      }
      else {
        $name = $skin_name;
        $title = $skin_title;
        $style = $skin_name;
      }

      $elements[$skin_name][$name . '_checkboxes'] = [
        '#type' => 'checkboxes',
        '#title' => "$title checkboxes ($style)",
        '#options_display' => 'side_by_side',
        '#options' => [
          'one' => 'One',
          'two' => 'Two',
          'three' => 'Three',
        ],
        '#default_value' => 'one',
        '#wrapper_attributes' => ['class' => 'container-inline'],
        '#icheck' => $style,
      ];
      $elements[$skin_name][$name . '_radios'] = [
        '#type' => 'radios',
        '#title' => "$title radios ($style)",
        '#options_display' => 'side_by_side',
        '#options' => [
          'one' => 'One',
          'two' => 'Two',
          'three' => 'Three',
        ],
        '#default_value' => 'one',
        '#icheck' => $style,
      ];
    }
  }
  return $elements;
}

/**
 * Generate test elements #icheck options.
 */
function _webform_test_test_element_icheck_options() {
  $skins = [
    'minimal' => t('Minimal'),
    'flat' => t('Flat'),
    'square' => t('Square'),
  ];
  $colors = [
    'square' => t('Black'),
    'grey' => t('Grey'),
    'yellow' => t('Yellow'),
    'orange' => t('Orange'),
    'red' => t('Red'),
    'pink' => t('Pink'),
    'purple' => t('Purple'),
    'blue' => t('Blue'),
    'green' => t('Green'),
    'aero' => t('Aero'),
  ];
  $icheck_options = ['' => ''];
  foreach ($skins as $icheck_skin_name => $icheck_skin_title) {
    $icheck_skin_title = (string) $icheck_skin_title;
    $icheck_options[$icheck_skin_title] = [];
    foreach ($colors as $icheck_color_name => $icheck_color_title) {
      if (file_exists(DRUPAL_ROOT . "/libraries/jquery.icheck/skins/$icheck_skin_name/_all.css")) {
        if (file_exists(DRUPAL_ROOT . "/libraries/jquery.icheck/skins/$icheck_skin_name/$icheck_color_name.css")) {
          $icheck_options[$icheck_skin_title]["$icheck_skin_name-$icheck_color_name"] = $icheck_color_title;
        }
      }
      else {
        $icheck_options[$icheck_skin_title]["$icheck_skin_name"] = $icheck_skin_title;
      }
    }
  }
  return $icheck_options;
}
