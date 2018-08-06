<?php

/**
 * @file
 * Generate examples of all elements.
 */

use Drupal\Component\Utility\Unicode;

/**
 * Generate examples of all elements.
 *
 * @return array
 *   An array containing examples of all elements.
 */
function webform_test_example_elements() {
  // Elements to be ignored.
  $skipped_elements = [
    'table',
    'webform_flexbox',
    'webform_test',
    'webform_wizard_page',
  ];

  /** @var \Drupal\webform\WebformElementManagerInterface $element_manager */
  $element_manager = \Drupal::service('plugin.manager.webform.element');

  $definitions = $element_manager->getDefinitions();
  $definitions = $element_manager->getSortedDefinitions($definitions);
  $elements = $element_manager->getInstances();

  $data = [
    'basic_elements' => [],
    'advanced_elements' => [],
  ];
  foreach ($definitions as $definition) {
    $element_type = $definition['id'];
    if (in_array($element_type, $skipped_elements)) {
      continue;
    }

    if (!empty($definition['composite'])) {
      continue;
    }

    $webform_element = $elements[$element_type];
    $element = _webform_test_get_example_element($element_type);
    if (!$element) {
      continue;
    }

    $category_name = (string) $webform_element->getPluginDefinition()['category'] ?: 'Other elements';
    $category_id = preg_replace('/[^a-zA-Z0-9]+/', '_', Unicode::strtolower($category_name));
    if (empty($data[$category_id])) {
      $data[$category_id] = [
        '#type' => 'details',
        '#title' => $category_name,
        '#open' => TRUE,
      ];
    }
    $data[$category_id][$element_type] = $element;

    // Multiple value composite.
    if ($webform_element->supportsMultipleValues() && !$webform_element->hasMultipleValues($element)) {
      unset($element['#description']);
      $properties = ['tags', 'multiple'];
      foreach ($properties as $property) {
        if ($webform_element->hasProperty($property)) {
          $multiple_element = $element;
          $multiple_element['#title'] = $element['#title'] . ' ' . $property;
          $multiple_element["#$property"] = TRUE;
          if ($property == 'multiple' && $webform_element->hasProperty('select2')) {
            $multiple_element['#select2'] = TRUE;
          }
          $data[$category_id][$element_type . '_' . $property] = $multiple_element;
        }
      }
    }
  }

  // Move other elements last.
  if (isset($data['other_elements'])) {
    $other_elements = $data['other_elements'];
    unset($data['other_elements']);
    $data['other_elements'] = $other_elements;
  }

  return $data;
}
