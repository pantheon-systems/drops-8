<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Utility\WebformAccessibilityHelper;
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
      '#selector_sources' => [],
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

    $element['#state_options_flattened'] = OptGroup::flattenOptions($element['#state_options']);
    $element['#selector_options_flattened'] = OptGroup::flattenOptions($element['#selector_options']);

    $element['#tree'] = TRUE;

    $edit_source = $form_state->get(static::getStorageKey($element, 'edit_source'));

    // Add validate callback that extracts the associative array of states.
    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [get_called_class(), 'validateWebformElementStates']);

    // For customized #states display a CodeMirror YAML editor.
    $warning_message = static::isDefaultValueCustomizedFormApiStates($element);
    if ($warning_message || $edit_source) {
      if ($warning_message) {
        $warning_message .= ' ' . t('Form API #states must be manually entered.');
        $element['warning_messages'] = [
          '#type' => 'webform_message',
          '#message_type' => 'warning',
          '#message_message' => $warning_message,
        ];
      }

      if ($edit_source) {
        $element['edit_source_message'] = [
          '#type' => 'webform_message',
          '#message_message' => t('Creating custom conditional logic (Form API #states) with nested conditions or custom selectors will disable the conditional logic builder. This will require that Form API #states be manually entered.'),
          '#message_type' => 'info',
          '#message_close' => TRUE,
          '#message_storage' => WebformMessage::STORAGE_SESSION,
        ];
      }
      $element['states'] = [
        '#type' => 'webform_codemirror',
        '#title' => t('Conditional Logic (YAML)'),
        '#title_display' => 'invisible',
        '#mode' => 'yaml',
        '#default_value' => WebformYaml::encode($element['#default_value']),
        '#description' => t('Learn more about Drupal\'s <a href=":href">Form API #states</a>.', [':href' => 'https://www.lullabot.com/articles/form-api-states']),
        '#webform_element' => TRUE,
        '#more_title' => t('Help'),
        '#more' => static::buildSourceHelp($element),
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
      ['data' => t('Element'), 'width' => '50%'],
      ['data' => t('Trigger/Value'), 'width' => '25%'],
      ['data' => WebformAccessibilityHelper::buildVisuallyHidden(t('Operations'))],
    ];

    // Get states and number of rows.
    if (($form_state->isRebuilding())) {
      $states = $element['#value'];
    }
    else {
      $states = (isset($element['#default_value'])) ? static::convertFormApiStatesToStatesArray($element['#default_value']) : [];
    }

    // Track state row indexes for disable/enabled warning message.
    $state_row_indexes = [];

    // Build state and conditions rows.
    $row_index = 0;
    $rows = [];
    foreach ($states as $state_settings) {
      $rows[$row_index] = static::buildStateRow($element, $state_settings, $table_id, $row_index, $ajax_settings);
      $state_row_indexes[] = $row_index;
      $row_index++;
      foreach ($state_settings['conditions'] as $condition) {
        $rows[$row_index] = static::buildConditionRow($element, $condition, $table_id, $row_index, $ajax_settings);
        $row_index++;
      }
    }

    // Generator empty state with conditions rows.
    if ($row_index < $number_of_rows) {
      $rows[$row_index] = static::buildStateRow($element, [], $table_id, $row_index, $ajax_settings);
      $state_row_indexes[] = $row_index;
      $row_index++;
      while ($row_index < $number_of_rows) {
        $rows[$row_index] = static::buildConditionRow($element, [], $table_id, $row_index, $ajax_settings);
        $row_index++;
      }
    }

    // Add wrapper to the element.
    $element += ['#prefix' => '', '#suffix' => ''];
    $element['#prefix'] = '<div id="' . $table_id . '">' . $element['#prefix'];
    $element['#suffix'] .= '</div>';

    // Build table.
    $element['states'] = [
      '#type' => 'table',
      '#header' => $header,
      '#attributes' => ['class' => ['webform-states-table']],
    ] + $rows;

    $element['actions'] = ['#type' => 'container'];
    // Build add state action.
    if ($element['#multiple']) {
      $element['actions']['add'] = [
        '#type' => 'submit',
        '#value' => t('Add another state'),
        '#limit_validation_errors' => [],
        '#submit' => [[get_called_class(), 'addStateSubmit']],
        '#ajax' => $ajax_settings,
        '#name' => $table_id . '_add',
      ];
    }

    // Edit source.
    if (\Drupal::currentUser()->hasPermission('edit webform source')) {
      $element['actions']['source'] = [
        '#type' => 'submit',
        '#value' => t('Edit source'),
        '#limit_validation_errors' => [],
        '#submit' => [[get_called_class(), 'editSourceSubmit']],
        '#ajax' => $ajax_settings,
        '#attributes' => ['class' => ['button', 'button--danger']],
        '#name' => $table_id . '_source',
      ];
    }

    // Display a warning message when any state is set to disabled or enabled.
    if (!empty($element['#disabled_message'])) {
      $total_state_row_indexes = count($state_row_indexes);
      $triggers = [];
      foreach ($state_row_indexes as $index => $row_index) {
        $id = Html::getId('edit-' . implode('-', $element['#parents']) . '-states-' . $row_index . '-state');
        $triggers[] = [':input[data-drupal-selector="' . $id . '"]' => ['value' => ['pattern' => '^(disabled|enabled)$']]];
        if (($index + 1) < $total_state_row_indexes) {
          $triggers[] = 'or';
        }
      }
      if ($triggers) {
        $element['disabled_message'] = [
          '#type' => 'webform_message',
          '#message_message' => t('<a href="https://www.w3schools.com/tags/att_input_disabled.asp">Disabled</a> elements do not submit data back to the server and the element\'s server-side default or current value will be preserved and saved to the database.'),
          '#message_type' => 'warning',
          '#states' => ['visible' => $triggers],
        ];
      }
    }

    $element['#attached']['library'][] = 'webform/webform.element.states';

    // Convert #options to jQuery autocomplete source format.
    // @see http://api.jqueryui.com/autocomplete/#option-source
    $selectors = [];
    $sources = [];
    if ($element['#selector_sources']) {
      foreach ($element['#selector_sources'] as $selector => $values) {
        $sources_key = Crypt::hashBase64(serialize($values));
        $selectors[$selector] = $sources_key;
        if (!isset($sources[$sources_key])) {
          foreach ($values as $key => $value) {
            $sources[$sources_key][] = [
              'label' => (string) $value . ($value != $key ? ' (' . $key . ')' : ''),
              'value' => (string) $key,
            ];
          }
        }
      }
    }
    $element['#attached']['drupalSettings']['webformElementStates'] = [
      'selectors' => $selectors,
      'sources' => $sources,
    ];

    return $element;
  }

  /**
   * Build edit source help.
   *
   * @param array $element
   *   An element.
   *
   * @return array
   *   A renderable array.
   */
  protected static function buildSourceHelp(array $element) {
    $build = [];
    $build['states'] = [
      'title' => [
        '#markup' => t('Available states'),
        '#prefix' => '<strong>',
        '#suffix' => '</strong>',
      ],
      'items' => static::convertOptionToItemList($element['#state_options']),
    ];
    if ($element['#selector_options']) {
      $build['selectors'] = [
        'title' => [
          '#markup' => t('Available selectors'),
          '#prefix' => '<strong>',
          '#suffix' => '</strong>',
        ],
        'items' => static::convertOptionToItemList($element['#selector_options']),
      ];
    }
    $build['triggers'] = [
      'title' => [
        '#markup' => t('Available triggers'),
        '#prefix' => '<strong>',
        '#suffix' => '</strong>',
      ],
      'items' => static::convertOptionToItemList($element['#trigger_options']),
    ];
    return $build;
  }

  /**
   * Convert options with optgroup to item list.
   *
   * @param array $options
   *   An array of options.
   *
   * @return array
   *   A renderable array.
   */
  protected static function convertOptionToItemList(array $options) {
    $items = [];
    foreach ($options as $option_name => $option_value) {
      if (is_array($option_value)) {
        $items[$option_name] = [
          'title' => [
            '#markup' => $option_name,
          ],
          'children' => [
            '#theme' => 'item_list',
            '#items' => array_keys($option_value),
          ],
        ];
      }
      else {
        $items[$option_name] = [
          '#markup' => $option_name,
          '#prefix' => '<div>',
          '#suffix' => '</div>',
        ];
      }
    }
    return [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
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
      '#title' => t('State'),
      '#title_display' => 'invisible',
      '#options' => $element['#state_options'],
      '#default_value' => $state['state'],
      '#empty_option' => t('- Select -'),
      '#wrapper_attributes' => ['class' => ['webform-states-table--state']],
      '#error_no_message' => TRUE,
    ];
    $row['operator'] = [
      '#type' => 'select',
      '#title' => t('Operator'),
      '#title_display' => 'invisible',
      '#options' => [
        'and' => t('All'),
        'or' => t('Any'),
        'xor' => t('One'),
      ],
      '#default_value' => $state['operator'],
      '#field_prefix' => t('if'),
      '#field_suffix' => t('of the following is met:'),
      '#wrapper_attributes' => ['class' => ['webform-states-table--operator'], 'colspan' => 2, 'align' => 'left'],
      '#error_no_message' => TRUE,
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
      '#type' => 'select',
      '#title' => t('Selector'),
      '#title_display' => 'invisible',
      '#options' => $element['#selector_options'],
      '#wrapper_attributes' => ['class' => ['webform-states-table--selector']],
      '#default_value' => $condition['selector'],
      '#empty_option' => t('- Select -'),
      '#error_no_message' => TRUE,
    ];
    if (!isset($element['#selector_options_flattened'][$condition['selector']])) {
      $row['selector']['#options'][$condition['selector']] = $condition['selector'];
    }
    $row['condition'] = [
      '#wrapper_attributes' => ['class' => ['webform-states-table--condition']],
    ];
    $row['condition']['trigger'] = [
      '#type' => 'select',
      '#title' => t('Trigger'),
      '#title_display' => 'invisible',
      '#options' => $element['#trigger_options'],
      '#default_value' => $condition['trigger'],
      '#empty_option' => t('- Select -'),
      '#parents' => [$element_name, 'states', $row_index , 'trigger'],
      '#wrapper_attributes' => ['class' => ['webform-states-table--trigger']],
      '#error_no_message' => TRUE,
    ];
    $row['condition']['value'] = [
      '#type' => 'textfield',
      '#title' => t('Value'),
      '#title_display' => 'invisible',
      '#size' => 25,
      '#default_value' => $condition['value'],
      '#placeholder' => t('Enter value…'),
      '#states' => [
        'visible' => [
          [$trigger_selector => ['value' => 'value']],
          'or',
          [$trigger_selector => ['value' => '!value']],
          'or',
          [$trigger_selector => ['value' => 'pattern']],
          'or',
          [$trigger_selector => ['value' => '!pattern']],
          'or',
          [$trigger_selector => ['value' => 'greater']],
          'or',
          [$trigger_selector => ['value' => 'less']],
          'or',
          [$trigger_selector => ['value' => 'between']],
        ],
      ],
      '#wrapper_attributes' => ['class' => ['webform-states-table--value']],
      '#parents' => [$element_name, 'states', $row_index , 'value'],
      '#error_no_message' => TRUE,
    ];
    $row['condition']['pattern'] = [
      '#type' => 'container',
      'description' => ['#markup' => t('Enter a <a href=":href">regular expression</a>', [':href' => 'http://www.w3schools.com/js/js_regexp.asp'])],
      '#states' => [
        'visible' => [
          [$trigger_selector => ['value' => 'pattern']],
          'or',
          [$trigger_selector => ['value' => '!pattern']],
        ],
      ],
    ];
    $row['condition']['pattern'] = [
      '#type' => 'container',
      'description' => ['#markup' => t('Enter a number range (1:100)')],
      '#states' => [
        'visible' => [
          [$trigger_selector => ['value' => 'between']],
        ],
      ],
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
      '#title' => t('Add'),
      '#src' => drupal_get_path('module', 'webform') . '/images/icons/plus.svg',
      '#limit_validation_errors' => [],
      '#submit' => [[get_called_class(), 'addConditionSubmit']],
      '#ajax' => $ajax_settings,
      '#row_index' => $row_index,
      '#name' => $table_id . '_add_' . $row_index,
    ];
    $operations['remove'] = [
      '#type' => 'image_button',
      '#title' => t('Remove'),
      '#src' => drupal_get_path('module', 'webform') . '/images/icons/minus.svg',
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
   * Form submission handler for adding another state.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function addStateSubmit(array &$form, FormStateInterface $form_state) {
    // Get the webform states element by going up one level.
    $button = $form_state->getTriggeringElement();
    $element =& NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));

    $values = $element['states']['#value'];

    // Add new state and condition.
    $values[] = [
      'state' => '',
      'operator' => 'and',
    ];
    $values[] = [
      'selector' => '',
      'trigger' => '',
      'value' => '',
    ];

    // Update element's #value.
    $form_state->setValueForElement($element['states'], $values);
    NestedArray::setValue($form_state->getUserInput(), $element['states']['#parents'], $values);

    // Update the number of rows.
    $form_state->set(static::getStorageKey($element, 'number_of_rows'), count($values));

    // Rebuild the form.
    $form_state->setRebuild();
  }

  /**
   * Form submission handler for adding another condition.
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

    // Rebuild the form.
    $form_state->setRebuild();
  }

  /**
   * Form submission handler for removing a state or condition.
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

    // Rebuild the form.
    $form_state->setRebuild();
  }

  /**
   * Form submission handler for editing source.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function editSourceSubmit(array &$form, FormStateInterface $form_state) {
    // Get the webform states element by going up one level.
    $button = $form_state->getTriggeringElement();
    $element =& NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));

    // Set edit source.
    $form_state->set(static::getStorageKey($element, 'edit_source'), TRUE);

    // Convert states to editable string.
    $value = $element['#value'] ? Yaml::encode($element['#value']) : '';
    $form_state->setValueForElement($element['states'], $value);
    NestedArray::setValue($form_state->getUserInput(), $element['states']['#parents'], $value);

    // Rebuild the form.
    $form_state->setRebuild();
  }

  /**
   * Webform submission Ajax callback the returns the states table.
   */
  public static function ajaxCallback(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $parent_length = (isset($button['#row_index'])) ? -4 : -2;
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, $parent_length));
    return $element;
  }

  /**
   * Validates webform states element.
   */
  public static function validateWebformElementStates(&$element, FormStateInterface $form_state, &$complete_form) {
    if (isset($element['states']['#value']) && is_string($element['states']['#value'])) {
      $states = Yaml::decode($element['states']['#value']);
    }
    else {
      $errors = [];
      $states = static::convertElementValueToFormApiStates($element, $errors);
      if ($errors) {
        $form_state->setError($element, reset($errors));
      }
    }
    $form_state->setValueForElement($element, NULL);

    $element['#value'] = $states;
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
          $states[$index]['conditions'][] = static::getStatesArrayCondition($condition_key, $condition_value);
        }
        elseif (is_string($condition_value)) {
          $states[$index]['operator'] = $condition_value;
        }
        else {
          foreach ($condition_value as $subcondition_key => $subcondition_value) {
            $states[$index]['conditions'][] = static::getStatesArrayCondition($subcondition_key, $subcondition_value);
          }
        }
      }
      $index++;
    }
    return $states;
  }

  /**
   * Get states array condition.
   *
   * @param string $selector
   *   The selector.
   * @param array $condition
   *   The condition.
   *
   * @return array
   *   Associative array container selector, trigger, and value.
   */
  protected static function getStatesArrayCondition($selector, array $condition) {
    $trigger = key($condition);
    $value = reset($condition);
    if (is_array($value)) {
      return static::getStatesArrayCondition($selector, $value);
    }
    return ['selector' => $selector, 'trigger' => $trigger, 'value' => $value];
  }

  /**
   * Convert an element's submitted value to Form API #states.
   *
   * @param array $element
   *   The form element.
   * @param array $errors
   *   An array used to capture errors.
   *
   * @return array
   *   An associative array of states.
   */
  protected static function convertElementValueToFormApiStates(array $element, array &$errors = []) {
    $states = [];
    $states_array = static::convertFormValuesToStatesArray($element['states']['#value']);
    foreach ($states_array as $state_array) {
      $state = $state_array['state'];
      if (!$state) {
        continue;
      }

      // Check for duplicate states.
      if (isset($states[$state])) {
        static::setFormApiStateError($element, $errors, $state);
      }

      // Define values extracted from
      // WebformElementStates::getFormApiStatesCondition().
      $selector = NULL;
      $trigger = NULL;
      $value = NULL;

      $operator = $state_array['operator'];
      $conditions = $state_array['conditions'];
      if (count($conditions) === 1) {
        $condition = reset($conditions);
        extract(static::getFormApiStatesCondition($condition));
        // Check for duplicate selectors.
        if (isset($states[$state][$selector])) {
          static::setFormApiStateError($element, $errors, $state, $selector);
        }
        $states[$state][$selector][$trigger] = $value;
      }
      else {
        foreach ($state_array['conditions'] as $index => $condition) {
          extract(static::getFormApiStatesCondition($condition));
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
              // Check for duplicate selectors.
              if (isset($states[$state][$selector])) {
                static::setFormApiStateError($element, $errors, $state, $selector);
              }
              $states[$state][$selector] = [
                $trigger => $value,
              ];
            }
          }
        }
      }
    }
    return $states;
  }

  /**
   * Set Form API state error.
   *
   * @param array $element
   *   The form element.
   * @param array $errors
   *   An array used to capture errors.
   * @param null|string $state
   *   An element state.
   * @param null|string $selector
   *   An element selector.
   */
  protected static function setFormApiStateError(array $element, array &$errors, $state = NULL, $selector = NULL) {
    $state_options = $element['#state_options_flattened'];
    $selector_options = $element['#selector_options_flattened'];

    if ($state && !$selector) {
      $t_args = [
        '%state' => $state_options[$state],
      ];
      $errors[] = t('The %state state is declared more than once. There can only be one declaration per state.', $t_args);
    }
    elseif ($state && $selector) {
      $t_args = [
        '%selector' => $selector_options[$selector],
        '%state' => $state_options[$state],
      ];
      $errors[] = t('The %selector element is used more than once within the %state state. To use multiple values within a trigger try using the pattern trigger.', $t_args);
    }
  }

  /**
   * Get FAPI states array condition.
   *
   * @param array $condition
   *   The condition.
   *
   * @return array
   *   Associative array container selector, trigger, and value.
   */
  protected static function getFormApiStatesCondition(array $condition) {
    $selector = $condition['selector'];
    $trigger = $condition['trigger'];
    if ($selector && $trigger) {
      if (in_array($trigger, ['value', '!value'])) {
        $value = $condition['value'];
      }
      elseif (in_array($trigger, ['pattern', '!pattern', 'less', 'greater', 'between'])) {
        $value = [$trigger => $condition['value']];
        $trigger = 'value';
      }
      else {
        $value = TRUE;
      }
    }
    else {
      $value = '';
    }
    return [
      'selector' => $selector,
      'trigger' => $trigger,
      'value' => $value,
    ];
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
  protected static function convertFormValuesToStatesArray(array $values = []) {
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
        $states[$index]['conditions'][] = $value;
      }
    }
    return $states;
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
  protected static function isDefaultValueCustomizedFormApiStates(array $element) {
    // Empty default values are not customized.
    if (empty($element['#default_value'])) {
      return FALSE;
    }

    // #states must always be an array.
    if (!is_array($element['#default_value'])) {
      return t('Conditional logic (Form API #states) is not an array.');
    }

    $state_options = OptGroup::flattenOptions($element['#state_options']);
    $states = $element['#default_value'];
    foreach ($states as $state => $conditions) {
      if (!isset($state_options[$state])) {
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
            return t('Conditional logic (Form API #states) is using the %operator operator.', ['%operator' => mb_strtoupper($condition)]);
          }

          // Make sure the same operator is being used between the conditions.
          if ($operator && $operator != $condition) {
            return t('Conditional logic (Form API #states) has multiple operators.', ['%operator' => mb_strtoupper($condition)]);
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
    $visibility_optgroup = (string) t('Visibility');
    $state_optgroup = (string) t('State');
    $validation_optgroup = (string) t('Validation');
    $value_optgroup = (string) t('Value');
    return [
      $visibility_optgroup => [
        'visible' => t('Visible'),
        'invisible' => t('Hidden'),
        'visible-slide' => t('Visible (Slide)'),
        'invisible-slide' => t('Hidden (Slide)'),
      ],
      $state_optgroup => [
        'enabled' => t('Enabled'),
        'disabled' => t('Disabled'),
        'readwrite' => t('Read/write'),
        'readonly' => t('Read-only'),
        'expanded' => t('Expanded'),
        'collapsed' => t('Collapsed'),
      ],
      $validation_optgroup => [
        'required' => t('Required'),
        'optional' => t('Optional'),
      ],
      $value_optgroup => [
        'checked' => t('Checked'),
        'unchecked' => t('Unchecked'),
      ],
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
      'value' => t('Value is'),
      '!value' => t('Value is not'),
      'pattern' => t('Pattern'),
      '!pattern' => t('Not Pattern'),
      'less' => t('Less than'),
      'greater' => t('Greater than'),
      'between' => t('Between'),
    ];
  }

}
