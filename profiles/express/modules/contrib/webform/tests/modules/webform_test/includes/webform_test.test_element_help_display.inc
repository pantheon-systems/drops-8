<?php

/**
 * @file
 * Generate test elements help displays.
 */

use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;

/**
 * Generate test elements help displays.
 *
 * @return array
 *   An array containing test elements with different help displays.
 */
function webform_test_test_element_help_display() {
  /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
  $element_manager = \Drupal::service('plugin.manager.webform.element');

  $definitions = $element_manager->getDefinitions();
  $definitions = $element_manager->getSortedDefinitions($definitions);
  $elements = $element_manager->getInstances();

  $data = [
    'basic_elements' => [],
    'advanced_elements' => [],
  ];
  foreach ($definitions as $element_type => $definition) {
    $webform_element = $elements[$element_type];
    $element = _webform_test_get_element_preview($element_type);
    if (!$element) {
      continue;
    }

    if (!$webform_element->hasProperty('help_display')) {
      continue;
    }

    if ($webform_element instanceof WebformCompositeBase) {
      continue;
    }

    $category_name = (string) $webform_element->getPluginDefinition()['category'] ?: 'Other elements';
    $category_id = preg_replace('/[^a-zA-Z0-9]+/', '_', mb_strtolower($category_name));
    if (empty($data[$category_id])) {
      $data[$category_id] = [
        '#type' => 'details',
        '#title' => $category_name,
        '#open' => TRUE,
      ];
    }

    $help_displays = [
      'title_before' => 'title_before',
      'title_after' => 'title_after',
      'element_before' => 'element_before',
      'element_after' => 'element_after',
    ];

    $data[$category_id][$element_type . '_help'] = [
      '#markup' => (isset($element['#title'])) ? $element['#title'] . ' (' . $element_type . ')' : $element_type,
      '#prefix' => '<h3>',
      '#suffix' => '</h3>',
    ];
    foreach ($help_displays as $help_display) {
      $example_element = $element;
      $example_element['#help'] = 'This is help.';
      $example_element['#help_display'] = $help_display;
      if (isset($element['#title'])) {
        $example_element['#title'] .= ' (' . $help_display . ')';
      }
      else {
        $example_element['#help'] .= ' (' . $help_display . ')';
      }
      $data[$category_id][$element_type . '_' . $help_display] = $example_element;
    }
    $data[$category_id][$element_type . '_hr'] = ['#type' => 'webform_horizontal_rule'];

  }

  // Move other elements last.
  if (isset($data['other_elements'])) {
    $other_elements = $data['other_elements'];
    unset($data['other_elements']);
    $data['other_elements'] = $other_elements;
  }

  return $data;
}
