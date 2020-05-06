<?php

/**
 * @file
 * Generate test elements with Flexbox wrappers.
 */

use Drupal\Core\Serialization\Yaml;

/**
 * Generate test elements with Flexbox wrappers.
 *
 * @return array
 *   An array containing test elements with Flexbox wrappers.
 */
function webform_test_test_element_flexbox() {
  // Element types to be ignored.
  $skipped_element_types = [
    'value',
    'webform_attachment_token',
    'webform_attachment_twig',
    'webform_attachment_url',
    'webform_entity_print_attachment:pdf',
  ];

  $data = [
    'containers' => [],
    'elements' => [],
  ];

  /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
  $element_manager = \Drupal::service('plugin.manager.webform.element');
  $elements = $element_manager->getInstances();
  foreach ($elements as $element_type => $webform_element) {
    if (in_array($element_type, $skipped_element_types)) {
      continue;
    }

    $element = _webform_test_get_element_preview($webform_element->getTypeName());
    if (!$element) {
      continue;
    }

    $group = ($webform_element->isContainer($element)) ? 'containers' : 'elements';
    $left_element = $element;
    if (isset($left_element['#title'])) {
      $left_element['#title'] .= ' 01';
    }
    $right_element = $element;
    if (isset($right_element['#title'])) {
      $right_element['#title'] .= ' 02';
    }
    $data[$group]['flexbox_' . $element_type] = [
      '#type' => 'webform_flexbox',
      'flex_' . $element_type . '_left' => $left_element ,
      'flex_' . $element_type . '_right' => $right_element,
    ];
  }

  $yaml = file_get_contents(drupal_get_path('module', 'webform_test') . '/includes/webform_test.test_element_flexbox.yml');
  $default_elements = Yaml::decode($yaml);

  return $data['containers'] + $data['elements'] + $default_elements;
}
