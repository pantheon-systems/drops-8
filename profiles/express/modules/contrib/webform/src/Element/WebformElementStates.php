<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Serialization\Yaml;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\Utility\WebformYaml;

/**
 * Provides a webform element to edit an element's #states.
 *
 * @FormElement("webform_element_states")
 */
class WebformElementStates extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#selector_options' => [],
      '#empty_states' => 3,
      '#process' => [
        [$class, 'processWebformStates'],
      ],
      '#theme_wrappers' => ['form_element'],
      '#multiple' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      if (isset($element['#default_value'])) {
        if (is_string($element['#default_value'])) {
          $default_value = Yaml::decode($element['#default_value']);
        }
        else {
          $default_value = $element['#default_value'] ?: [];
        }
        return static::convertFormApiStatesToStatesArray($default_value);
      }
      else {
        return [];
      }
    }
    elseif (is_array($input) && isset($input['states'])) {
      return (is_string($input['states'])) ? Yaml::decode($input['states']) : static::convertFormValuesToStatesArray($input['states']);
    }
    else {
      return [];
    }
  }

  /**
   * Expand an email confirm field into two HTML5 email elements.
   */
  public static function processWebformStates(&$element, FormStateInterface $form_state, &$complete_form) {
    // Define default #state_options and #trigger_options.
    // There are also defined by \Drupal\webform\Plugin\WebformElementBase::form.
    $element += [
      '#state_options' => static::getStateOptions(),
      '#trigger_options' => static::getTriggerOptions(),
    ];

    $element['#tree'] = TRUE;

    // Add validate callback that extracts the associative array of states.
    $element['#element_validate'] = [[get_called_class(), 'validateWebformElementStates']];

    // For customized #states display a CodeMirror YAML editor.
    if ($warning_message = static::isDefaultValueCustomizedFormApiStates($element)) {
      $warning_message .= ' ' . t('Form API #states must be manually entered.');
      $element['messages'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => $warning_message,
      ];
      $element['states'] = [
        '#type' => 'webform_codemirror',
        '#mode' => 'yaml',
        '#default_value' => WebformYaml::tidy(Yaml::encode($element['#default_value'])),
        '#description' => t('Learn more about Drupal\'s <a href=":href">Form API #states</a>.', [':href' => 'https://www.lullabot.com/articles/form-api-states']),
      ];
      return $element;
    }

    $table_id = implode('_', $element['#parents']) . '_table';

    // Store the number of rows.
    $storage_key = static::getStorageKey($element, 'number_of_rows');
    if ($form_state->get($storage_key) === NULL) {
      if (empty($element['#default_value']) || !is_array($element['#default_value'])) {
        $number_of_rows = 2;
      }
      else {
        $number_of_rows = count($element['#default_value']);
      }
      $form_state->set($storage_key, $number_of_rows);
    }
    $number_of_rows = $form_state->get($storage_key);

    // DEBUG: Disable Ajax callback by commenting out the below callback and
    // wrapper.
    $ajax_settings = [
      'callback' => [get_called_class(), 'ajaxCallback'],
      'wrapper' => $table_id,
      'progress' => ['type' => 'none'],
    ];

    // Build header.
    $header = [
      ['data' => t('State'), 'width' => '25%'],
      ['data' => t('Element/Selector'), 'width' => '50%'],
      ['data' => t('Trigger/Value'), 'width' => '25%'],
      ['data' => ''],
    ];

    // Get states and number of rows.
    if (($form_state->isRebuilding())) {
      $states = $element['#value'];
    }
    else {
      $states = (isset($element['#default_value'])) ? static::convertFormApiStatesToStatesArray($element['#default_value']) : [];
    }

    // Build state and conditions rows.
    $row_index = 0;
    $rows = [];
    foreach ($states as $state_settings) {
      $rows[$row_index] = static::buildStateRow($element, $state_settings, $table_id, $row_index, $ajax_settings);
      $row_index++;
      foreach ($state_settings['conditions'] as $condition) {
        $rows[$row_index] = static::buildConditionRow($element, $condition, $table_id, $row_index, $ajax_settings);
        $row_index++;
      }
    }

    // Generator empty state with conditions rows.
    if ($row_index < $number_of_rows) {
      $rows[$row_index] = static::buildStateRow($element, [], $table_id, $row_index, $ajax_settings);;
      $row_index++;
      while ($row_index < $number_of_rows) {
        $rows[$row_index] = static::buildConditionRow($element, [], $table_id, $row_index, $ajax_settings);
        $row_index++;
      }
    }

    // Build table.
    $element['states'] = [
      '#prefix' => '<div id="' . $table_id . '" class="webform-states-table">',
      '#suffix' => '</div>',
      '#type' => 'table',
      '#header' => $header,
    ] + $rows;

    // Build add state action.
    if ($element['#multiple']) {
      $element['add'] = [
        '#type' => 'submit',
        '#value' => t('Add another state'),
        '#limit_validation_errors' => [],
        '#submit' => [[get_called_class(), 'addStateSubmit']],
        '#ajax' => $ajax_settings,
        '#name' => $table_id . '_add',
      ];
    }

    $element['#attached']['library'][] = 'webform/webform.element.states';

    return $element;
  }

  /**
   * Build state row.
   *
   * @param array $element
   *   The element.
   * @param array $state
   *   The state.
   * @param string $table_id
   *   The element's table id.
   * @param int $row_index
   *   The row index.
   * @param array $ajax_settings
   *   An array containing Ajax callback settings.
   *
   * @return array
   *   A render array containing a state table row.
   */
  protected static function buildStateRow(array $element, array $state, $table_id, $row_index, array $ajax_settings) {
    $state += ['state' => '', 'operator' => 'and'];
    $row = [
      '#attributes' => [
        'class' => ['webform-states-table--state'],
      ],
    ];
    $row['state'] = [
      '#type' => 'select',
      '#options' => $element['#state_options'],
      '#default_value' => $state['state'],
      '#empty_option' => '',
      '#empty_value' => '',
      '#wrapper_attributes' => ['class' => ['webform-states-table--state']],
    ];
    $row['operator'] = [
      '#type' => 'select',
      '#options' => [
        'and' => t('All'),
        'or' => t('Any'),
        'xor' => t('One'),
      ],
      '#default_value' => $state['operator'],
      '#field_prefix' => t('if'),
      '#field_suffix' => t('of the following is met:'),
      '#wrapper_attributes' => ['class' => ['webform-states-table--operator'], 'colspan' => 2, 'align' => 'left'],
    ];
    $row['operations'] = static::buildOperations($table_id, $row_index, $ajax_settings);
    if (!$element['#multiple']) {
      unset($row['operations']['remove']);
    }
    return $row;
  }

  /**
   * Build condition row.
   *
   * @param array $element
   *   The element.
   * @param array $condition
   *   The condition.
   * @param string $table_id
   *   The element's table id.
   * @param int $row_index
   *   The row index.
   * @param array $ajax_settings
   *   An array containing Ajax callback settings.
   *
   * @return array
   *   A render array containing a condition table row.
   */
  protected static function buildConditionRow(array $element, array $condition, $table_id, $row_index, array $ajax_settings) {
    $condition += ['selector' => '', 'trigger' => '', 'value' => ''];

    $element_name = $element['#name'];
    $trigger_selector = ":input[name=\"{$element_name}[states][{$row_index}][trigger]\"]";

    $row = [
      '#attributes' => [
        'class' => ['webform-states-table--condition'],
      ],
    ];
    $row['state'] = [];
    $row['selector'] = [
      '#type' => 'webform_select_other',
      '#options' => $element['#selector_options'],
      '#other__option_label' => t('Custom selector...'),
      '#other__placeholder' => t('Enter custom selector...'),
      '#wrapper_attributes' => ['class' => ['webform-states-table--selector']],
      '#default_value' => $condition['selector'],
      '#empty_option' => '',
      '#empty_value' => '',
    ];
    $row['condition'] = [
      '#wrapper_attributes' => ['class' => ['webform-states-table--condition']]
    ];
    $row['condition']['trigger'] = [
      '#type' => 'select',
      '#options' => $element['#trigger_options'],
      '#default_value' => $condition['trigger'],
      '#empty_option' => '',
      '#empty_value' => '',
      '#parents' => [$element_name, 'states', $row_index , 'trigger'],
      '#wrapper_attributes' => ['class' => ['webform-states-table--trigger']],
    ];
    $row['condition']['value'] = [
      '#type' => 'textfield',
      '#title' => t('Value'),
      '#title_display' => 'invisible',
      '#size' => 25,
      '#default_value' => $condition['value'],
      '#placeholder' => t('Enter value...'),
      '#states' => [
        'visible' => [
          [$trigger_selector => ['value' => 'value']],
          'or',
          [$trigger_selector => ['value' => '!value']],
        ],
      ],
      '#wrapper_attributes' => ['class' => ['webform-states-table--value']],
      '#parents' => [$element_name, 'states', $row_index , 'value'],
    ];
    $row['operations'] = static::buildOperations($table_id, $row_index, $ajax_settings);
    return $row;
  }

  /**
   * Build a state's operations.
   *
   * @param string $table_id
   *   The option element's table id.
   * @param int $row_index
   *   The option's row index.
   * @param array $ajax_settings
   *   An array containing Ajax callback settings.
   *
   * @return array
   *   A render array containing state operations.
   */
  protected static function buildOperations($table_id, $row_index, array $ajax_settings) {
    $operations = [
      '#wrapper_attributes' => ['class' => ['webform-states-table--operations']],
    ];
    $operations['add'] = [
      '#type' => 'image_button',
      '#src' => drupal_get_path('module', 'webform') . '/images/icons/plus.svg',
      '#limit_validation_errors' => [],
      '#submit' => [[get_called_class(), 'addConditionSubmit']],
      '#ajax' => $ajax_settings,
      '#row_index' => $row_index,
      '#name' => $table_id . '_add_' . $row_index,
    ];
    $operations['remove'] = [
      '#type' => 'image_button',
      '#src' => drupal_get_path('module', 'webform') . '/images/icons/ex.svg',
      '#limit_validation_errors' => [],
      '#submit' => [[get_called_class(), 'removeRowSubmit']],
      '#ajax' => $ajax_settings,
      '#row_index' => $row_index,
      '#name' => $table_id . '_remove_' . $row_index,
    ];
    return $operations;
  }

  /****************************************************************************/
  // Callbacks.
  /****************************************************************************/

  /**
   * Webform submission handler for adding another state.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function addStateSubmit(array &$form, FormStateInterface $form_state) {
    // Get the webform states element by going up one level.
    $button = $form_state->getTriggeringElement();
    $element =& NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));

    $values = $element['states']['#value'];

    // Add new state and condition.
    $values[] = [
      'state' => '',
      'operator' => 'and',
    ];
    $values[] = [
      'selector' => ['select' => '', 'other' => ''],
      'trigger' => '',
      'value' => '',
    ];

    // Update element's #value.
    $form_state->setValueForElement($element['states'], $values);
    NestedArray::setValue($form_state->getUserInput(), $element['states']['#parents'], $values);

    // Update the number of rows.
    $form_state->set(static::getStorageKey($element, 'number_of_rows'), count($values));

    // Rebuild the webform.
    $form_state->setRebuild();
  }

  /**
   * Webform submission handler for adding another condition.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function addConditionSubmit(array &$form, FormStateInterface $form_state) {
    // Get the webform states element by going up one level.
    $button = $form_state->getTriggeringElement();
    $element =& NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -4));

    // The $row_index is not sequential so we need to rebuild the value instead
    // of just using an array_slice().
    $row_index = $button['#row_index'];
    $values = [];
    foreach ($element['states']['#value'] as $index => $value) {
      $values[] = $value;
      if ($index == $row_index) {
        $values[] = ['selector' => '', 'trigger' => '', 'value' => ''];
      }
    }

    // Reset values.
    $values = array_values($values);

    // Set values.
    $form_state->setValueForElement($element['states'], $values);
    NestedArray::setValue($form_state->getUserInput(), $element['states']['#parents'], $values);

    // Update the number of rows.
    $form_state->set(static::getStorageKey($element, 'number_of_rows'), count($values));

    // Rebuild the webform.
    $form_state->setRebuild();
  }

  /**
   * Webform submission handler for removing a state or condition.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function removeRowSubmit(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -4));

    $row_index = $button['#row_index'];
    $values = $element['states']['#value'];

    if (isset($values[$row_index]['state'])) {
      // Remove state.
      do {
        unset($values[$row_index]);
        $row_index++;
      } while (isset($values[$row_index]) && !isset($values[$row_index]['state']));
    }
    else {
      // Remove condition.
      unset($values[$row_index]);
    }

    // Reset values.
    $values = array_values($values);

    // Set values.
    $form_state->setValueForElement($element['states'], $values);
    NestedArray::setValue($form_state->getUserInput(), $element['states']['#parents'], $values);

    // Update the number of rows.
    $form_state->set(static::getStorageKey($element, 'number_of_rows'), count($values));

    // Rebuild the webform.
    $form_state->setRebuild();
  }

  /**
   * Webform submission Ajax callback the returns the states table.
   */
  public static function ajaxCallback(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $parent_length = (isset($button['#row_index'])) ? -4 : -1;
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, $parent_length));
    return $element['states'];
  }

  /**
   * Validates webform states element.
   */
  public static function validateWebformElementStates(&$element, FormStateInterface $form_state, &$complete_form) {
    if (isset($element['states']['#value']) && is_string($element['states']['#value'])) {
      $states = Yaml::decode($element['states']['#value']);
    }
    else {
      $states = static::convertFormValuesToFormApiStates($element['states']['#value']);
    }
    $form_state->setValueForElement($element, NULL);
    $form_state->setValueForElement($element, $states);
  }

  /****************************************************************************/
  // Helper functions.
  /****************************************************************************/

  /**
   * Get unique key used to store the number of options for an element.
   *
   * @param array $element
   *   An element.
   * @param string $name
   *   The name.
   *
   * @return string
   *   A unique key used to store the number of options for an element.
   */
  protected static function getStorageKey(array $element, $name) {
    return 'webform_states__' . $element['#name'] . '__' . $name;
  }

  /****************************************************************************/
  // Convert functions.
  /****************************************************************************/

  /**
   * Convert Form API #states to states array.
   *
   * @param array $fapi_states
   *   An associative array containing Form API #states.
   *
   * @return array
   *   An associative array of states.
   */
  protected static function convertFormApiStatesToStatesArray(array $fapi_states) {
    $index = 0;
    $states = [];
    foreach ($fapi_states as $state => $conditions) {
      $states[$index] = [
        'state' => $state,
        'operator' => 'and',
        'conditions' => [],
      ];

      foreach ($conditions as $condition_key => $condition_value) {
        if (is_string($condition_key)) {
          $states[$index]['conditions'][] = [
            'selector' => $condition_key,
            'trigger' => key($condition_value),
            'value' => reset($condition_value),
          ];
        }
        elseif (is_string($condition_value)) {
          $states[$index]['operator'] = $condition_value;
        }
        else {
          foreach ($condition_value as $subcondition_key => $subcondition_value) {
            $states[$index]['conditions'][] = [
              'selector' => $subcondition_key,
              'trigger' => key($subcondition_value),
              'value' => reset($subcondition_value),
            ];
          }
        }
      }
      $index++;
    }
    return $states;
  }

  /**
   * Convert states array to Form API #states.
   *
   * @param array $states_array
   *   An associative array containing states.
   *
   * @return array
   *   An associative array of states.
   */
  protected static function convertStatesArrayToFormApiStates(array $states_array = []) {
    $states = [];
    foreach ($states_array as $state_array) {
      if ($state = $state_array['state']) {
        $operator = $state_array['operator'];
        $conditions = $state_array['conditions'];
        if (count($conditions) === 1) {
          $condition = reset($conditions);
          $selector = $condition['selector'];
          $trigger = $condition['trigger'];
          if ($selector && $trigger) {
            $value = (in_array($trigger, ['value', '!value'])) ? $condition['value'] : TRUE;
          }
          else {
            $value = '';
          }
          $states[$state][$selector][$trigger] = $value;
        }
        else {
          foreach ($state_array['conditions'] as $index => $condition) {
            $selector = $condition['selector'];
            $trigger = $condition['trigger'];
            $value = (in_array($trigger, ['value', '!value'])) ? $condition['value'] : TRUE;
            if ($selector && $trigger) {
              if ($operator == 'or' || $operator == 'xor') {
                if ($index !== 0) {
                  $states[$state][] = $operator;
                }
                $states[$state][] = [
                  $selector => [
                    $trigger => $value,
                  ],
                ];
              }
              else {
                $states[$state][$selector] = [
                  $trigger => $value,
                ];
              }
            }
          }
        }
      }
    }
    return $states;
  }

  /**
   * Convert webform values to states array.
   *
   * @param array $values
   *   Submitted webform values to converted to states array.
   *
   * @return array
   *   An associative array of states.
   */
  public static function convertFormValuesToStatesArray(array $values = []) {
    $index = 0;

    $states = [];
    foreach ($values as $value) {
      if (isset($value['state'])) {
        $index++;
        $states[$index] = [
          'state' => $value['state'],
          'operator' => (isset($value['operator'])) ? $value['operator'] : 'and',
          'conditions' => [],
        ];
      }
      else {
        if (isset($value['selector']['select'])) {
          $selector = $value['selector']['select'];
          if ($selector == WebformSelectOther::OTHER_OPTION) {
            $selector = $value['selector']['other'];
          }
          $value['selector'] = $selector;
        }
        $states[$index]['conditions'][] = $value;
      }
    }
    return $states;
  }

  /**
   * Convert webform values to states array.
   *
   * @param array $values
   *   Submitted webform values to converted to states array.
   *
   * @return array
   *   An associative array of states.
   */
  public static function convertFormValuesToFormApiStates(array $values = []) {
    $values = static::convertFormValuesToStatesArray($values);
    return static::convertStatesArrayToFormApiStates($values);
  }

  /**
   * Determine if an element's #states array is customized.
   *
   * @param array $element
   *   The element.
   *
   * @return bool|string
   *   FALSE if #states array is not customized or a warning message.
   */
  public static function isDefaultValueCustomizedFormApiStates(array $element) {
    // Empty default values are not customized.
    if (empty($element['#default_value'])) {
      return FALSE;
    }

    // #states must always be an array.
    if (!is_array($element['#default_value'])) {
      return t('Conditional logic (Form API #states) is not an array.');
    }

    $states = $element['#default_value'];
    foreach ($states as $state => $conditions) {
      if (!isset($element['#state_options'][$state])) {
        return t('Conditional logic (Form API #states) is using a custom %state state.', ['%state' => $state]);
      }

      // If associative array we can assume that it not customized.
      if (WebformArrayHelper::isAssociative(($conditions))) {
        $trigger = reset($conditions);
        if (count($trigger) > 1) {
          return t('Conditional logic (Form API #states) is using multiple triggers.');
        }
        continue;
      }

      $operator = NULL;
      foreach ($conditions as $condition) {
        // Make sure only one condition is being specified.
        if (is_array($condition) && count($condition) > 1) {
          return t('Conditional logic (Form API #states) is using multiple nested conditions.');
        }
        elseif (is_string($condition)) {
          if (!in_array($condition, ['and', 'or', 'xor'])) {
            return t('Conditional logic (Form API #states) is using the %operator operator.', ['%operator' => Unicode::strtoupper($condition)]);
          }

          // Make sure the same operator is being used between the conditions.
          if ($operator && $operator != $condition) {
            return t('Conditional logic (Form API #states) has multiple operators.', ['%operator' => Unicode::strtoupper($condition)]);
          }

          // Set the operator.
          $operator = $condition;
        }
      }
    }
    return FALSE;
  }

  /**
   * Get an associative array of translated state options.
   *
   * @return array
   *   An associative array of translated state options.
   */
  public static function getStateOptions() {
    return [
      'visible' => t('Visible'),
      'invisible' => t('Hidden'),
      'enabled' => t('Enabled'),
      'disabled' => t('Disabled'),
      'required' => t('Required'),
      'optional' => t('Optional'),
      'checked' => t('Checked'),
      'unchecked' => t('Unchecked'),
      'expanded' => t('Expanded'),
      'collapsed' => t('Collapsed'),
    ];
  }

  /**
   * Get an associative array of translated trigger options.
   *
   * @return array
   *   An associative array of translated trigger options.
   */
  public static function getTriggerOptions() {
    return [
      'empty' => t('Empty'),
      'filled' => t('Filled'),
      'checked' => t('Checked'),
      'unchecked' => t('Unchecked'),
      'expanded' => t('Expanded'),
      'collapsed' => t('Collapsed'),
      'value' => t('Value is'),
      '!value' => t('Value is not'),
    ];
  }

}
