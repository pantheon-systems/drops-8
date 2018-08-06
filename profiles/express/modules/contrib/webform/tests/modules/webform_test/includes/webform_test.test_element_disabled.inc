<?php

/**
 * @file
 * Generate test disabled elements.
 */

/**
 * Generate test elements with #disabled set to TRUE.
 *
 * @return array
 *   An array containing elements with #disabled set to TRUE.
 */
function webform_test_test_element_disabled() {
  /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
  $element_manager = \Drupal::service('plugin.manager.webform.element');
  $elements = $element_manager->getInstances();
  foreach ($elements as $element_type => $webform_element) {
    $element = _webform_test_get_element_preview($webform_element->getTypeName());
    if (!$element) {
      continue;
    }

    // Skip elements with the disabled property.
    if (!$webform_element->hasProperty('disabled')) {
      continue;
    }

    // Set disabled.
    $element['#disabled'] = TRUE;

    $data[$element_type] = $element;
  }

  return $data;
}
