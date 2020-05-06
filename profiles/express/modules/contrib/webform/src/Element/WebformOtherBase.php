<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Render\Element\FormElement;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformOptionsHelper;

/**
 * Base class for webform other element.
 */
abstract class WebformOtherBase extends FormElement {

  use WebformCompositeFormElementTrait;

  /**
   * Other option value.
   */
  const OTHER_OPTION = '_other_';

  /**
   * The type of element.
   *
   * @var string
   */
  protected static $type;

  /**
   * The properties of the element.
   *
   * @var array
   */
  protected static $properties = [
    '#title',
    '#required',
    '#required_error',
    '#options',
    '#options_display',
    '#options_randomize',
    '#options_description_display',
    '#options__properties',
    '#default_value',
    '#attributes',
  ];

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processWebformOther'],
        [$class, 'processAjaxForm'],
      ],
      '#pre_render' => [
        [$class, 'preRenderWebformCompositeFormElement'],
      ],
      '#options' => [],
      '#other__option_delimiter' => ', ',
      '#states' => [],
      // Add '#markup' property to add an 'id' attribute to the form element.
      // @see template_preprocess_form_element()
      '#markup' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    // Remove 'webform_' prefix from type.
    $type = str_replace('webform_', '', static::$type);

    if ($input === FALSE) {
      $value = static::convertDefaultValueToElementValue($element);
      $element[$type]['#default_value'] = $value[$type];
      if ($value['other'] !== NULL) {
        $element['other']['#default_value'] = $value['other'];
      }
      return $value;
    }

    // Return NULL so that current $input is used.
    return NULL;
  }

  /**
   * Processes an 'other' element.
   *
   * See select list webform element for select list properties.
   *
   * @see \Drupal\Core\Render\Element\Select
   */
  public static function processWebformOther(&$element, FormStateInterface $form_state, &$complete_form) {
    // Remove 'webform_' prefix from type.
    $type = str_replace('webform_', '', static::$type);
    $properties = static::$properties;

    $element['#tree'] = TRUE;

    $element[$type]['#type'] = static::$type;
    $element[$type]['#webform_element'] = TRUE;
    $element[$type] += array_intersect_key($element, array_combine($properties, $properties));
    $element[$type]['#title_display'] = 'invisible';
    if (!isset($element[$type]['#options'][static::OTHER_OPTION])) {
      $element[$type]['#options'][static::OTHER_OPTION] = (!empty($element['#other__option_label'])) ? $element['#other__option_label'] : t('Other…');
    }
    $element[$type]['#error_no_message'] = TRUE;

    // Build other textfield.
    $element += ['other' => []];
    foreach ($element as $key => $value) {
      if (strpos($key, '#other__') === 0) {
        $other_key = str_replace('#other__', '#', $key);
        if (!isset($element['other'][$other_key])) {
          $element['other'][$other_key] = $value;
        }
      }
    }
    $element['other'] += [
      '#type' => 'textfield',
      '#webform_element' => TRUE,
      '#placeholder' => t('Enter other…'),
    ];
    if (!isset($element['other']['#title'])) {
      $element['other'] += [
        '#title' => $element['other']['#placeholder'] ,
        '#title_display' => 'invisible',
      ];
    }

    $element['other']['#wrapper_attributes']['class'][] = "js-webform-$type-other-input";
    $element['other']['#wrapper_attributes']['class'][] = "webform-$type-other-input";

    if ($element['other']['#type'] == 'datetime') {
      $element['other']['#prefix'] = '<div class="' . implode(' ', $element['other']['#wrapper_attributes']['class']) . '">';
      $element['other']['#suffix'] = '</div>';
      unset($element['other']['#wrapper_attributes']['class']);
    }

    // Apply #parents to $type and other element.
    if (isset($element['#parents'])) {
      $element[$type]['#parents'] = array_merge($element['#parents'], [$type]);
      $element['other']['#parents'] = array_merge($element['#parents'], ['other']);
    }

    // Initialize the type and other elements to allow for webform enhancements.
    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');
    $element_manager->buildElement($element[$type], $complete_form, $form_state);
    $element_manager->buildElement($element['other'], $complete_form, $form_state);

    // Prevent nested fieldset by removing fieldset theme wrapper around
    // radios and checkboxes.
    // @see \Drupal\Core\Render\Element\CompositeFormElementTrait
    $element[$type]['#pre_render'] = [];

    // Add js trigger attributes to the composite wrapper.
    // @see \Drupal\webform\Element\WebformCompositeFormElementTrait
    $is_form_element_wrapper = (isset($element['#wrapper_type']) && $element['#wrapper_type'] === 'form_element');
    $wrapper_attributes = ($is_form_element_wrapper) ? '#wrapper_attributes' : '#attributes';
    $element[$wrapper_attributes]['class'][] = "js-webform-$type-other";
    $element[$wrapper_attributes]['class'][] = "webform-$type-other";

    // Apply the element id to the wrapper so that inline form errors point
    // to the correct element.
    $element['#attributes']['id'] = $element['#id'];

    // Make sure form element label has no 'for' attribute.
    $element['#label_attributes']['webform-remove-for-attribute'] = TRUE;

    // Remove options.
    unset($element['#options']);

    // Add validate callback.
    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [get_called_class(), 'validateWebformOther']);

    // Attach library.
    $element['#attached']['library'][] = 'webform/webform.element.other';

    // Process states.
    webform_process_states($element, '#wrapper_attributes');

    return $element;
  }

  /**
   * Validates an other element.
   */
  public static function validateWebformOther(&$element, FormStateInterface $form_state, &$complete_form) {
    $type = static::getElementType();

    // Determine if the element is visible. (#access !== FALSE)
    $has_access = (!isset($element['#access']) || $element['#access'] === TRUE);

    // Determine if the element has multiple values.
    $is_multiple = static::isMultiple($element);

    // Get value.
    $value = NestedArray::getValue($form_state->getValues(), $element['#parents']);

    // Get return value.
    $return_value = static::processValue($element, $value);

    // Determine if the return value is empty.
    if ($is_multiple) {
      $is_empty = (empty($return_value)) ? TRUE : FALSE;
    }
    else {
      $is_empty = ($return_value === '' || $return_value === NULL) ? TRUE : FALSE;
    }

    // Determine if there is an other value and is the other value empty.
    $element_value = (array) $value[$type];
    $other_value = $value['other'];
    if ($element_value) {
      $element_value = array_filter($element_value);
      $element_value = array_combine($element_value, $element_value);
    }
    $other_is_empty = (isset($element_value[static::OTHER_OPTION]) && $other_value === '');

    // Display missing other or missing value error.
    if ($has_access) {
      $required_error_title = (isset($element['#title'])) ? $element['#title'] : NULL;
      if ($other_is_empty) {
        WebformElementHelper::setRequiredError($element['other'], $form_state, $required_error_title);
      }
      elseif ($element['#required'] && $is_empty) {
        WebformElementHelper::setRequiredError($element, $form_state, $required_error_title);
        $element['other']['#error_no_message'] = TRUE;
      }
    }

    $form_state->setValueForElement($element[$type], NULL);
    $form_state->setValueForElement($element['other'], NULL);

    $element['#value'] = $return_value;
    $form_state->setValueForElement($element, $return_value);
  }

  /**
   * Processed other element's submitted value.
   *
   * @param array $element
   *   The element.
   * @param array $value
   *   The submitted value.
   *
   * @return array|string
   *   An array of values or a string.
   */
  public static function processValue(array $element, array $value) {
    $type = static::getElementType();

    $element_value = $value[$type];
    $other_value = $value['other'];

    if (static::isMultiple($element)) {
      $return_value = array_filter($element_value);
      $return_value = array_combine($return_value, $return_value);
      if (isset($return_value[static::OTHER_OPTION])) {
        unset($return_value[static::OTHER_OPTION]);
        if ($other_value !== '') {
          $return_value += [$other_value => $other_value];
        }
      }
      return $return_value;
    }
    else {
      return ($element_value === static::OTHER_OPTION) ? $other_value : $element_value;
    }
  }

  /****************************************************************************/
  // Helper functions.
  /****************************************************************************/

  /**
   * Get the element type.
   *
   * @return string
   *   The element type.
   */
  public static function getElementType() {
    // Remove 'webform_' prefix from type.
    return str_replace('webform_', '', static::$type);
  }

  /**
   * Determine if the webform element contains multiple values.
   *
   * @param array $element
   *   A webform element.
   *
   * @return bool
   *   TRUE if the webform element contains multiple values.
   */
  protected static function isMultiple(array $element) {
    return (!empty($element['#multiple']) || static::$type == 'checkboxes') ? TRUE : FALSE;
  }

  /**
   * Convert default value to element value.
   *
   * @param array $element
   *   A other form element.
   *
   * @return array
   *   An associative array container (element) type and other value.
   */
  protected static function convertDefaultValueToElementValue(array $element) {
    $type = str_replace('webform_', '', static::$type);

    $default_value = isset($element['#default_value']) ? $element['#default_value'] : NULL;
    if (static::isMultiple($element)) {
      // Handle edge case where $default_value is not an array.
      if (!is_array($default_value)) {
        return [$type => [], 'other' => NULL];
      }

      $default_options = array_combine($default_value, $default_value);
      $flattened_options = OptGroup::flattenOptions($element['#options']);
      if ($other_options = array_diff_key($default_options, $flattened_options)) {
        return [
          $type => array_diff_key($default_options, $other_options) + [static::OTHER_OPTION => static::OTHER_OPTION],
          'other' => implode($element['#other__option_delimiter'], $other_options),
        ];
      }

      return [$type => $default_options, 'other' => NULL];
    }
    else {
      if (!empty($default_value) && !WebformOptionsHelper::hasOption($default_value, $element['#options'])) {
        return [$type => static::OTHER_OPTION, 'other' => $default_value];
      }

      return [$type => $default_value, 'other' => NULL];
    }
  }

}
