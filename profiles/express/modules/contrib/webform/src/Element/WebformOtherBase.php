<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Render\Element\FormElement;
use Drupal\webform\Utility\WebformOptionsHelper;

/**
 * Base class for webform other element.
 */
abstract class WebformOtherBase extends FormElement {

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
    '#required',
    '#options',
    '#options_display',
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
      '#theme_wrappers' => ['form_element'],
      '#options' => [],
      '#other__option_delimiter' => ', ',
      '#states' => [],
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

    $element['#wrapper_attributes']['class'][] = "js-webform-$type-other";
    $element['#wrapper_attributes']['class'][] = "webform-$type-other";

    if (!empty($element['#required'])) {
      $element['#wrapper_attributes']['required'] = 'required';
      $element['#wrapper_attributes']['aria-required'] = 'aria-required';
    }

    $element[$type]['#type'] = static::$type;
    $element[$type] += array_intersect_key($element, array_combine($properties, $properties));
    if (!isset($element[$type]['#options'][static::OTHER_OPTION])) {
      $element[$type]['#options'][static::OTHER_OPTION] = (!empty($element['#other__option_label'])) ? $element['#other__option_label'] : t('Other...');
    }
    $element[$type]['#error_no_message'] = TRUE;

    // Disable label[for] which does not point to any specific element.
    // @see webform_preprocess_form_element_label()
    if (in_array($type, ['radios', 'checkboxes', 'buttons'])) {
      $element['#label_attributes']['for'] = FALSE;
    }

    // Build other textfield.
    $element['other']['#error_no_message'] = TRUE;
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
      '#placeholder' => t('Enter other...'),
    ];

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

    // Remove options.
    unset($element['#options']);

    // Set validation.
    if (isset($element['#element_validate'])) {
      $element['#element_validate'] = array_merge([[get_called_class(), 'validateWebformOther']], $element['#element_validate']);
    }
    else {
      $element['#element_validate'] = [[get_called_class(), 'validateWebformOther']];
    }

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
    // Determine if the element is visible. (#access !== FALSE)
    $has_access = (!isset($element['#access']) || $element['#access'] === TRUE);

    // Remove 'webform_' prefix from type.
    $type = str_replace('webform_', '', static::$type);

    // Get value.
    $value = NestedArray::getValue($form_state->getValues(), $element['#parents']);

    // Get return value.
    $return_value = [];
    $element_value = $value[$type];
    $other_value = $value['other'];
    if (static::isMultiple($element)) {
      $element_value = array_filter($element_value);
      $element_value = array_combine($element_value, $element_value);
      $return_value += $element_value;
      if (isset($return_value[static::OTHER_OPTION])) {
        unset($return_value[static::OTHER_OPTION]);
        if ($has_access && $other_value === '') {
          static::setOtherError($element, $form_state);
        }
        else {
          $return_value += [$other_value => $other_value];
        }
      }
    }
    else {
      $return_value = $element_value;
      if ($element_value == static::OTHER_OPTION) {
        if ($has_access && $other_value === '') {
          static::setOtherError($element, $form_state);
          $return_value = '';
        }
        else {
          $return_value = $other_value;
        }
      }
    }

    // Determine if the return value is empty.
    if (static::isMultiple($element)) {
      $is_empty = (empty($return_value)) ? TRUE : FALSE;
    }
    else {
      $is_empty = ($return_value === '' || $return_value === NULL) ? TRUE : FALSE;
    }

    // Handler required validation.
    if ($element['#required'] && $is_empty && $has_access) {
      static::setElementError($element, $form_state);
    }

    $form_state->setValueForElement($element[$type], NULL);
    $form_state->setValueForElement($element['other'], NULL);
    $form_state->setValueForElement($element, $return_value);
  }

  /****************************************************************************/
  // Helper functions.
  /****************************************************************************/

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

  /****************************************************************************/
  // Error functions.
  /****************************************************************************/

  /**
   * Set element required error.
   *
   * @param array $element
   *   The webform element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected static function setElementError(array &$element, FormStateInterface $form_state) {
    if (isset($element['#required_error'])) {
      $form_state->setError($element, $element['#required_error']);
    }
    elseif (isset($element['#title'])) {
      $form_state->setError($element, t('@name field is required.', ['@name' => $element['#title']]));
    }
    else {
      $form_state->setError($element);
    }
  }

  /**
   * Set element required error.
   *
   * @param array $element
   *   The webform element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected static function setOtherError(array &$element, FormStateInterface $form_state) {
    if (isset($element['#required_error'])) {
      $form_state->setError($element['other'], $element['#required_error']);
    }
    elseif (isset($element['#title'])) {
      $form_state->setError($element['other'], t('@name field is required.', ['@name' => $element['#title']]));
    }
    else {
      $form_state->setError($element['other']);
    }
  }

}
