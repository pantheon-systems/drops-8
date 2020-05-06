<?php

/**
 * @file
 * Generate test elements title displays.
 */

use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\Plugin\WebformElement\WebformManagedFileBase;

/**
 * Generate test elements title displays.
 *
 * @return array
 *   An array containing test elements with different title displays.
 */
function webform_test_test_element_title_display() {
  // Element types to be ignored.
  $skipped_element_types = [
    'language_select',
    'webform_composite',
    'webform_section',
    'value',
    'webform_attachment_token',
    'webform_attachment_twig',
    'webform_attachment_url',
    'webform_entity_print_attachment:pdf',
  ];

  // Element types where inline title is not supported.
  $inline_not_support_element_types = [
    'fieldset',
    'details',
    'webform_codemirror',
    'webform_email_confirm',
    'webform_htmleditor',
    'webform_image_select',
    'webform_likert',
    'webform_mapping',
    'webform_signature',
  ];

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
    if (in_array($element_type, $skipped_element_types)) {
      continue;
    }

    $webform_element = $elements[$element_type];
    $element = _webform_test_get_element_preview($element_type);
    if (!$element || !$webform_element->hasProperty('title_display')) {
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

    $title_displays = [
      'before' => 'before',
      'after' => 'after',
      'inline' => 'inline',
      'none' => 'none',
    ];
    if ($webform_element->isComposite() || $webform_element instanceof WebformManagedFileBase) {
      unset($title_displays['inline']);
    }
    // Remove unsupported title display from certain element types.
    if (in_array($element_type, $inline_not_support_element_types)) {
      unset($title_displays['inline']);
    }

    $data[$category_id][$element_type . '_title'] = [
      '#markup' => $element['#title'] . ' (' . $element_type . ')',
      '#prefix' => '<h3>',
      '#suffix' => '</h3>',
    ];
    foreach ($title_displays as $title_display) {
      $example_element = $element;
      $example_element['#title'] .= ' (' . $title_display . ')';
      $example_element['#title_display'] = $title_display;
      $data[$category_id][$element_type . '_' . $title_display] = $example_element;
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
