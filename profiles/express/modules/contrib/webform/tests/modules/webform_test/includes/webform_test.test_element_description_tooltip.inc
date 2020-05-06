<?php

/**
 * @file
 * Generate test elements with description tooltips.
 */

/**
 * Generate test element description tooltip.
 *
 * @return array
 *   An array containing test elements with description tooltips.
 */
function webform_test_test_element_description_tooltip() {
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
    if (!$element || !$webform_element->hasProperty('description_display')) {
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
    $element['#description'] = (string) t("This is a description for the '@type' element.", ['@type' => $element_type]);
    $element['#description_display'] = 'tooltip';

    $data[$category_id][$element_type] = $element;
  }

  // Move other elements last.
  if (isset($data['other_elements'])) {
    $other_elements = $data['other_elements'];
    unset($data['other_elements']);
    $data['other_elements'] = $other_elements;
  }

  return $data;
}
