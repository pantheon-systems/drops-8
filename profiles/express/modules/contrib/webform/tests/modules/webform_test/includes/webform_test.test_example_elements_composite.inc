<?php

/**
 * @file
 * Generate examples of all elements.
 */

/**
 * Generate examples of all composite elements.
 *
 * @return array
 *   An array containing examples of all elements.
 */
function webform_test_test_example_elements_composite() {
  /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
  $element_manager = \Drupal::service('plugin.manager.webform.element');

  $definitions = $element_manager->getDefinitions();
  $definitions = $element_manager->getSortedDefinitions($definitions);
  $elements = $element_manager->getInstances();

  $data = [];
  foreach ($definitions as $definition) {
    $element_type = $definition['id'];
    if (empty($definition['composite'])) {
      continue;
    }

    $webform_element = $elements[$element_type];
    $element = _webform_test_get_element_preview($element_type);
    if (!$element) {
      continue;
    }

    $data[$element_type . '_example'] = [
      '#type' => 'details',
      '#title' => $element['#title'] . '',
      '#open' => TRUE,
    ];

    // Single value composite.
    $data[$element_type . '_example'][$element_type] = $element;

    // Multiple value composite.
    if ($webform_element->supportsMultipleValues()) {
      $element['#title'] = $element['#title'] . ' multiple';
      $element['#multiple'] = TRUE;
      if ((!in_array($element_type, ['webform_contact']) && $webform_element->hasProperty('multiple__header'))) {
        $element['#multiple__header'] = TRUE;
      }
      $data[$element_type . '_example'][$element_type . '_multiple'] = $element;
    }
  }
  return $data;
}
