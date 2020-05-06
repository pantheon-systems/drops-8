<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Provides a webform element for checking a box before entering a value.
 *
 * @FormElement("webform_checkbox_value")
 */
class WebformCheckboxValue extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processWebformCheckboxValue'],
        [$class, 'processAjaxForm'],
      ],
      '#theme_wrappers' => ['form_element'],
      '#states' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $element += ['#default_value' => NULL];
    if ($input === FALSE) {
      return [
        'checkbox' => ($element['#default_value']) ? TRUE : FALSE,
        'value' => $element['#default_value'],
      ];
    }
    else {
      return $input;
    }
  }

  /**
   * Processes a 'webform_checkbox_value' element.
   */
  public static function processWebformCheckboxValue(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#tree'] = TRUE;

    $properties = [
      '#title' => '#title',
      '#description' => '#description',
      '#help' => '#help',
    ];

    // Build checkbox element.
    $element['checkbox'] = [
      '#type' => 'checkbox',
      '#default_value' => (!empty($element['#default_value'])) ? TRUE : FALSE,
    ];
    $element['checkbox'] += array_intersect_key($element, $properties);

    // Build value element.
    $selector = 'edit-' . str_replace('_', '-', implode('-', $element['#parents'])) . '-checkbox';
    $element['value'] = [
      '#default_value' => $element['#default_value'],
      '#states' => [
        'visible' => [
          ':input[data-drupal-selector="' . $selector . '"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[data-drupal-selector="' . $selector . '"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Pass '#value__*' properties to the value element.
    foreach ($element as $key => $value) {
      if (strpos($key, '#value__') === 0) {
        $value_key = str_replace('#value__', '#', $key);
        $element['value'][$value_key] = $value;
      }
    }

    // Pass entire element to the value element.
    if (isset($element['#element'])) {
      $element['value'] += $element['#element'];
    }

    // Make sure the value element has a #type.
    $element['value'] += ['#type' => 'textfield'];

    // Always add a title to the value element for validation.
    if (!isset($element['value']['#title']) && isset($element['#title'])) {
      $element['value']['#title'] = $element['#title'];
      $element['value']['#title_display'] = 'invisible';
    }

    // Attach libraries.
    $element['#attached']['library'][] = 'webform/webform.element.checkbox_value';

    // Add validate callback.
    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [get_called_class(), 'validateWebformCheckboxValue']);

    // Remove properties from the element.
    $element = array_diff_key($element, $properties);

    return $element;
  }

  /**
   * Validates a checkbox value element.
   */
  public static function validateWebformCheckboxValue(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = NestedArray::getValue($form_state->getValues(), $element['#parents']);

    // Always require a value when the checkbox is checked.
    if (!empty($value['checkbox']) && empty($value['value'])) {
      WebformElementHelper::setRequiredError($element['value'], $form_state);
    }

    // If checkbox is not checked then empty the value.
    if (empty($value['checkbox'])) {
      $value['value'] = '';
    }

    $form_state->setValueForElement($element['checkbox'], NULL);
    $form_state->setValueForElement($element['value'], NULL);

    $element['#value'] = $value['value'];
    $form_state->setValueForElement($element, $value['value']);
  }

}
