<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformOptionsHelper;
use Drupal\webform\Utility\WebformYaml;

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
      '#yaml' => FALSE,
      '#label' => t('option'),
      '#labels' => t('options'),
      '#min_items' => 3,
      '#empty_items' => 1,
      '#add_more_items' => 1,
      '#options_value_maxlength' => 255,
      '#options_text_maxlength' => 255,
      '#options_description' => FALSE,
      '#options_description_maxlength' => NULL,
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
      if (static::hasOptGroup($options)) {
        return $options;
      }
      return static::convertOptionsToValues($options, $element['#options_description']);
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
    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [get_called_class(), 'validateWebformOptions']);

    // Wrap this $element in a <div> that handle #states.
    WebformElementHelper::fixStatesWrapper($element);

    // For options with optgroup display a CodeMirror YAML editor.
    if (!empty($element['#yaml']) || (isset($element['#default_value']) && is_array($element['#default_value']) && static::hasOptGroup($element['#default_value']))) {
      // Build table.
      $element['options'] = [
        '#type' => 'webform_codemirror',
        '#mode' => 'yaml',
        '#default_value' => WebformYaml::encode($element['#default_value']),
        '#placeholder' => t('Enter custom options…'),
        '#description' => t('Key-value pairs MUST be specified as "safe_key: \'Some readable options\'". Use of only alphanumeric characters and underscores is recommended in keys. One option per line.') . '<br /><br />' .
          t('Option groups can be created by using just the group name followed by indented group options.'),
      ];
      return $element;
    }
    else {
      $t_args = ['@label' => isset($element['#label']) ? Unicode::ucfirst($element['#label']) : t('Options')];
      $properties = ['#label', '#labels', '#min_items', '#empty_items', '#add_more_items'];

      $element['options'] = array_intersect_key($element, array_combine($properties, $properties)) + [
        '#type' => 'webform_multiple',
        '#header' => TRUE,
        '#key' => 'value',
        '#default_value' => (isset($element['#default_value'])) ? static::convertOptionsToValues($element['#default_value'], $element['#options_description']) : [],
        '#add_more_input_label' => t('more @options', ['@options' => $element['#labels']]),
      ];

      if ($element['#options_description']) {
        $element['options']['#element'] = [
          'option_value' => [
            '#type' => 'container',
            '#title' => t('@label value', $t_args),
            '#help' => t('A unique value stored in the database.'),
            'value' => [
              '#type' => 'textfield',
              '#title' => t('@label value', $t_args),
              '#title_display' => 'invisible',
              '#placeholder' => t('Enter value…'),
              '#attributes' => ['class' => ['js-webform-options-sync']],
              '#maxlength' => $element['#options_value_maxlength'],
              '#error_no_message' => TRUE,
            ],
          ],
          'option_text' => [
            '#type' => 'container',
            '#title' => t('@label text / description', $t_args),
            '#help' => t('Enter text and description to be displayed on the form.'),
            'text' => [
              '#type' => 'textfield',
              '#title' => t('@label text', $t_args),
              '#title_display' => 'invisible',
              '#placeholder' => t('Enter text…'),
              '#maxlength' => $element['#options_text_maxlength'],
              '#error_no_message' => TRUE,
            ],
            'description' => [
              '#type' => 'textarea',
              '#title' => t('@label description', $t_args),
              '#title_display' => 'invisible',
              '#placeholder' => t('Enter description…'),
              '#rows' => 2,
              '#maxlength' => $element['#options_description_maxlength'],
              '#error_no_message' => TRUE,
            ],
          ],
        ];
      }
      else {
        $element['options']['#element'] = [
          'option_value' => [
            '#type' => 'container',
            '#title' => t('@label value', $t_args),
            '#help' => t('A unique value stored in the database.'),
            'value' => [
              '#type' => 'textfield',
              '#title' => t('@label value', $t_args),
              '#title_display' => 'invisible',
              '#placeholder' => t('Enter value…'),
              '#attributes' => ['class' => ['js-webform-options-sync']],
              '#maxlength' => $element['#options_value_maxlength'],
              '#error_no_message' => TRUE,
            ],
          ],
          'option_text' => [
            '#type' => 'container',
            '#title' => t('@label text', $t_args),
            '#help' => t('Text to be displayed on the form.'),
            'text' => [
              '#type' => 'textfield',
              '#title' => t('@label text', $t_args),
              '#title_display' => 'invisible',
              '#placeholder' => t('Enter text…'),
              '#maxlength' => $element['#options_text_maxlength'],
              '#error_no_message' => TRUE,
            ],
          ],
        ];
      }

      $element['#attached']['library'][] = 'webform/webform.element.options.admin';
      return $element;
    }
  }

  /**
   * Validates webform options element.
   */
  public static function validateWebformOptions(&$element, FormStateInterface $form_state, &$complete_form) {
    if ($form_state->hasAnyErrors()) {
      return;
    }

    $options_value = NestedArray::getValue($form_state->getValues(), $element['options']['#parents']);

    if (is_string($options_value)) {
      $options = Yaml::decode($options_value);
    }
    else {
      $options = static::convertValuesToOptions($options_value, $element['#options_description']);
    }

    // Validate required options.
    if (!empty($element['#required']) && empty($options)) {
      WebformElementHelper::setRequiredError($element, $form_state);
      return;
    }

    $element['#value'] = $options;
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
   * @param bool $options_description
   *   Options has description.
   *
   * @return array
   *   An array of options.
   */
  public static function convertValuesToOptions(array $values = NULL, $options_description = FALSE) {
    $options = [];
    if ($values && is_array($values)) {
      foreach ($values as $option_value => $option) {
        $option_text = $option['text'];
        if ($options_description && !empty($option['description'])) {
          $option_text .= WebformOptionsHelper::DESCRIPTION_DELIMITER . $option['description'];
        }

        // Populate empty option value or option text.
        if ($option_value === '') {
          $option_value = $option_text;
        }
        elseif ($option_text === '') {
          $option_text = $option_value;
        }

        $options[$option_value] = $option_text;
      }
    }
    return $options;
  }

  /**
   * Convert options to values for webform_multiple element.
   *
   * @param array $options
   *   An array of options.
   * @param bool $options_description
   *   Options has description.
   *
   * @return array
   *   An array of values.
   */
  public static function convertOptionsToValues(array $options = [], $options_description = FALSE) {
    $values = [];
    foreach ($options as $value => $text) {
      if ($options_description && strpos($text, WebformOptionsHelper::DESCRIPTION_DELIMITER) !== FALSE) {
        list($text, $description) = explode(WebformOptionsHelper::DESCRIPTION_DELIMITER, $text);
        $values[$value] = ['text' => $text, 'description' => $description];
      }
      else {
        $values[$value] = ['text' => $text];
      }
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
