<?php

/**
 * @file
 * Generate test elements with formatting.
 */

use Drupal\webform\WebformInterface;

/**
 * Generate test element formats.
 *
 * @return array
 *   An array containing test elements formats.
 */
function webform_test_test_element_format(WebformInterface $webform, $composite = FALSE, $multiple = FALSE) {
  // Element types to be ignored.
  $skipped_element_types = [
    'language_select',
    'text_format_raw',
    'text_format_value',
    'webform_composite',
    'webform_same',
    // @todo Fix file display
    'webform_audio_file',
    'webform_document_file',
    'webform_image_file',
    'webform_video_file',
    'webform_signature_raw',
  ];

  // Element default properties.
  $element_properties = [
    'managed_file' => [
      '#file_extensions' => 'txt',
    ],
  ];

  /** @var \Drupal\webform\WebformSubmissionGenerateInterface $submission_generate */
  $submission_generate = \Drupal::service('webform_submission.generate');

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
    $element = _webform_test_get_element_preview($element_type, ['issues' => FALSE]);
    if (!$element || !$webform_element->isInput($element)) {
      continue;
    }

    if ($webform_element->isComposite() !== $composite) {
      continue;
    }

    if ($multiple) {
      if (!$webform_element->hasMultipleValues($element) && !$webform_element->hasProperty('multiple') && !$webform_element->hasProperty('tags')) {
        continue;
      }

      // Set element #multiple or #tags property.
      $multiple_properties = ['multiple', 'tags'];
      foreach ($multiple_properties as $multiple_property) {
        if ($webform_element->hasProperty($multiple_property)) {
          $element["#$multiple_property"] = TRUE;
          break;
        }
      }
      $item_formats = [''];
      $items_formats = $webform_element->getItemsFormats();
    }
    else {
      if ($webform_element->hasMultipleValues($element)) {
        continue;
      }

      $item_formats = $webform_element->getItemFormats();
      $items_formats = [''];
    }

    // Set default (test) value.
    if (strpos($element_type, 'date') === 0) {
      $element['#default_value'] = ($multiple) ? ['1942-06-18', '1940-07-07', '1943-02-25'] : '1942-06-18';
    }
    elseif ($default_value = $submission_generate->getTestValue($webform, $element_type, $element, ['random' => FALSE])) {
      $element['#default_value'] = $default_value;
    }

    // Set default properties.
    if (isset($element_properties[$element_type])) {
      $element += $element_properties[$element_type];
    }

    // Set element category.
    $category_name = (string) $webform_element->getPluginDefinition()['category'] ?: 'Other elements';
    $category_id = preg_replace('/[^a-zA-Z0-9]+/', '_', mb_strtolower($category_name));
    if (empty($data[$category_id])) {
      $data[$category_id] = [
        '#type' => 'details',
        '#title' => $category_name,
        '#open' => TRUE,
      ];
    }
    $data[$category_id][$element_type] = [
      '#type' => 'details',
      '#title' => $element['#title'],
    ];
    foreach ($item_formats as $item_format_name => $item_format_title) {
      // Skip unsupported element type formats.
      if (in_array($element_type . '_' . $item_format_name, $skipped_element_types)) {
        continue;
      }

      foreach ($items_formats as $items_format_name => $items_format_title) {
        $format_element_type = implode('_', array_filter([$element_type, $item_format_name, $items_format_name]));
        $format_element_title = $element['#title'] . ' (' . implode(' - ', array_filter([$item_format_title, $items_format_title])) . ')';

        $format_element = $element;
        $format_element['#title'] = $format_element_title;
        if ($item_format_name) {
          $format_element['#format'] = $item_format_name;
        }
        if ($items_format_name) {
          $format_element['#format_items'] = $items_format_name;
        }
        $data[$category_id][$element_type][$format_element_type] = $format_element;
      }
    }
  }

  // Move other elements last.
  if (isset($data['other_elements'])) {
    $other_elements = $data['other_elements'];
    unset($data['other_elements']);
    $data['other_elements'] = $other_elements;
  }

  // Remove empty basic and advanced details.
  $data = array_filter($data);

  return $data;
}
