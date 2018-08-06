<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Provides a webform element to assist in creation of options.
 *
 * This provides a nicer interface for non-technical users to add values and
 * labels for options, possible within option groups.
 *
 * @FormElement("webform_options")
 */
class WebformOptions extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#label' => t('option'),
      '#labels' => t('options'),
      '#empty_items' => 5,
      '#add_more' => 1,
      '#process' => [
        [$class, 'processWebformOptions'],
      ],
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      if (!isset($element['#default_value'])) {
        return [];
      }

      $options = (is_string($element['#default_value'])) ? Yaml::decode($element['#default_value']) : $element['#default_value'];
      if (self::hasOptGroup($options)) {
        return $options;
      }
      return self::convertOptionsToValues($options);
    }
    elseif (is_array($input) && isset($input['options'])) {
      return (is_string($input['options'])) ? Yaml::decode($input['options']) : $input['options'];
    }
    else {
      return NULL;
    }
  }

  /**
   * Process options and build options widget.
   */
  public static function processWebformOptions(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#tree'] = TRUE;

    // Add validate callback that extracts the associative array of options.
    $element['#element_validate'] = [[get_called_class(), 'validateWebformOptions']];

    // Wrap this $element in a <div> that handle #states.
    WebformElementHelper::fixStatesWrapper($element);

    // For options with optgroup display a CodeMirror YAML editor.
    if (isset($element['#default_value']) && is_array($element['#default_value']) && self::hasOptGroup($element['#default_value'])) {
      // Build table.
      $element['options'] = [
        '#type' => 'webform_codemirror',
        '#mode' => 'yaml',
        '#default_value' => Yaml::encode($element['#default_value']),
        '#description' => t('Key-value pairs MUST be specified as "safe_key: \'Some readable options\'". Use of only alphanumeric characters and underscores is recommended in keys. One option per line.') . '<br/>' .
        t('Option groups can be created by using just the group name followed by indented group options.'),
      ];
      return $element;
    }
    else {
      $properties = ['#label', '#labels', '#empty_items', '#add_more'];
      $element['options'] = array_intersect_key($element, array_combine($properties, $properties)) + [
        '#type' => 'webform_multiple',
        '#header' => TRUE,
        '#element' => [
          'value' => [
            '#type' => 'textfield',
            '#title' => t('Option value'),
            '#title_display' => t('invisible'),
            '#placeholder' => t('Enter value'),
            '#maxlength' => 255,
          ],
          'text' => [
            '#type' => 'textfield',
            '#title' => t('Option text'),
            '#title_display' => t('invisible'),
            '#placeholder' => t('Enter text'),
            '#maxlength' => 255,
          ],
        ],
        '#default_value' => (isset($element['#default_value'])) ? self::convertOptionsToValues($element['#default_value']) : [],
      ];
      return $element;
    }
  }

  /**
   * Validates webform options element.
   */
  public static function validateWebformOptions(&$element, FormStateInterface $form_state, &$complete_form) {
    $options_value = NestedArray::getValue($form_state->getValues(), $element['options']['#parents']);

    if (is_string($options_value)) {
      $options = Yaml::decode($options_value);
    }
    else {
      $options = self::convertValuesToOptions($options_value);
    }

    // Validate required options.
    if (!empty($element['#required']) && empty($options)) {
      if (isset($element['#required_error'])) {
        $form_state->setError($element, $element['#required_error']);
      }
      elseif (isset($element['#title'])) {
        $form_state->setError($element, t('@name field is required.', ['@name' => $element['#title']]));
      }
      else {
        $form_state->setError($element);
      }
      return;
    }

    $form_state->setValueForElement($element, $options);
  }

  /****************************************************************************/
  // Helper functions.
  /****************************************************************************/

  /**
   * Convert values from yamform_multiple element to options.
   *
   * @param array $values
   *   An array of values.
   *
   * @return array
   *   An array of options.
   */
  public static function convertValuesToOptions(array $values = []) {
    $options = [];
    foreach ($values as $value) {
      $option_value = $value['value'];
      $option_text = $value['text'];

      // Populate empty option value or option text.
      if ($option_value === '') {
        $option_value = $option_text;
      }
      elseif ($option_text === '') {
        $option_text = $option_value;
      }

      $options[$option_value] = $option_text;
    }
    return $options;
  }

  /**
   * Convert options to values for yamform_multiple element.
   *
   * @param array $options
   *   An array of options.
   *
   * @return array
   *   An array of values.
   */
  public static function convertOptionsToValues(array $options = []) {
    $values = [];
    foreach ($options as $value => $text) {
      $values[] = ['value' => $value, 'text' => $text];
    }
    return $values;
  }

  /**
   * Determine if options array contains an OptGroup.
   *
   * @param array $options
   *   An array of options.
   *
   * @return bool
   *   TRUE if options array contains an OptGroup.
   */
  public static function hasOptGroup(array $options) {
    foreach ($options as $option_text) {
      if (is_array($option_text)) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
