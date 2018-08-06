<?php

/**
 * @file
 * Generate test elements with Flexbox wrappers.
 */

use Drupal\Component\Utility\Unicode;
use Drupal\webform\WebformInterface;

/**
 * Generate test element formats.
 *
 * @return array
 *   An array containing test elements formats.
 */
function webform_test_test_element_format(WebformInterface $webform, $composite = FALSE, $multiple = FALSE) {
  // Elements to be ignored.
  $skipped_elements = [
    'hidden',
    'language_select',
    'text_format_raw',
    'text_format_value',
    'value',
    'webform_element',
    'webform_test',
    // @todo Fix file display
    'managed_file',
    'webform_audio_file',
    'webform_document_file',
    'webform_image_file',
    'webform_video_file',
    'webform_signature_raw',
  ];

  /** @var \Drupal\webform\WebformSubmissionGenerateInterface $submission_generate */
  $submission_generate = \Drupal::service('webform_submission.generate');

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

    $webform_element = $elements[$element_type];
    $element = _webform_test_get_example_element($element_type, ['issues' => FALSE]);
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
    $element['#default_value'] = $submission_generate->getTestValue($webform, $element_type, $element, ['random' => FALSE]);

    // Set element category.
    $category_name = (string) $webform_element->getPluginDefinition()['category'] ?: 'Other elements';
    $category_id = preg_replace('/[^a-zA-Z0-9]+/', '_', Unicode::strtolower($category_name));
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
      if (in_array($element_type . '_' . $item_format_name, $skipped_elements)) {
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
