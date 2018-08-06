<?php

namespace Drupal\webform\Element;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformThemeHelper;

/**
 * Provides a webform element to assist in creation of multiple elements.
 *
 * @FormElement("webform_multiple")
 */
class WebformMultiple extends FormElement {

  /**
   * Value indicating a element accepts an unlimited number of values.
   */
  const CARDINALITY_UNLIMITED = -1;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#label' => t('item'),
      '#labels' => t('items'),
      '#key' => NULL,
      '#header' => NULL,
      '#element' => [
        '#type' => 'textfield',
        '#title' => t('Item value'),
        '#title_display' => 'invisible',
        '#placeholder' => t('Enter value'),
      ],
      '#cardinality' => FALSE,
      '#empty_items' => 1,
      '#add_more' => 1,
      '#process' => [
        [$class, 'processWebformMultiple'],
      ],
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      return (isset($element['#default_value'])) ? $element['#default_value'] : [];
    }
    elseif (is_array($input) && isset($input['items'])) {
      return $input['items'];
    }
    else {
      return NULL;
    }
  }

  /**
   * Process items and build multiple elements widget.
   */
  public static function processWebformMultiple(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#tree'] = TRUE;

    // Add validate callback that extracts the array of items.
    $element['#element_validate'] = [[get_called_class(), 'validateWebformMultiple']];

    // Wrap this $element in a <div> that handle #states.
    WebformElementHelper::fixStatesWrapper($element);

    if ($element['#cardinality']) {
      // If the cardinality is set limit number of items to this value.
      $number_of_items = $element['#cardinality'];
    }
    else {
      // Get unique key used to store the current number of items.
      $number_of_items_storage_key = self::getStorageKey($element, 'number_of_items');

      // Store the number of items which is the number of
      // #default_values + number of empty_items.
      if ($form_state->get($number_of_items_storage_key) === NULL) {
        if (empty($element['#default_value']) || !is_array($element['#default_value'])) {
          $number_of_default_values = 0;
        }
        else {
          $number_of_default_values = count($element['#default_value']);
        }
        $number_of_empty_items = (int) $element['#empty_items'];
        $number_of_items = $number_of_default_values + $number_of_empty_items;
        if ($number_of_items < 1) {
          $number_of_items = 1;
        }
        $form_state->set($number_of_items_storage_key, $number_of_items);
      }

      $number_of_items = $form_state->get($number_of_items_storage_key);
    }
    $table_id = implode('_', $element['#parents']) . '_table';

    // DEBUG: Disable AJAX callback by commenting out the below callback and
    // wrapper.
    $ajax_settings = [
      'callback' => [get_called_class(), 'ajaxCallback'],
      'wrapper' => $table_id,
    ];

    $element['#child_keys'] = Element::children($element['#element']);

    // Build (single) element header.
    $header = self::buildElementHeader($element);

    // Build (single) element rows.
    $row_index = 0;
    $weight = 0;
    $rows = [];

    if (!$form_state->isProcessingInput() && isset($element['#default_value']) && is_array($element['#default_value'])) {
      $default_values = $element['#default_value'];
    }
    elseif ($form_state->isProcessingInput() && isset($element['#value']) && is_array($element['#value'])) {
      $default_values = $element['#value'];
    }
    else {
      $default_values = [];
    }

    foreach ($default_values as $key => $default_value) {
      // If #key is defined make sure to set default value's key item.
      if (!empty($element['#key']) && !isset($default_value[$element['#key']])) {
        $default_value[$element['#key']] = $key;
      }
      $rows[$row_index] = self::buildElementRow($table_id, $row_index, $element, $default_value, $weight++, $ajax_settings);
      $row_index++;
    }

    while ($row_index < $number_of_items) {
      $rows[$row_index] = self::buildElementRow($table_id, $row_index, $element, NULL, $weight++, $ajax_settings);
      $row_index++;
    }

    // Build table.
    $element['items'] = [
      '#prefix' => '<div id="' . $table_id . '" class="webform-multiple-table">',
      '#suffix' => '</div>',
      '#type' => 'table',
      '#header' => $header,
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'webform-multiple-sort-weight',
        ],
      ],
    ] + $rows;

    // Build add items actions.
    if (empty($element['#cardinality'])) {
      $element['add'] = [
        '#prefix' => '<div class="container-inline">',
        '#suffix' => '</div>',
      ];
      $element['add']['submit'] = [
        '#type' => 'submit',
        '#value' => t('Add'),
        '#limit_validation_errors' => [],
        '#submit' => [[get_called_class(), 'addItemsSubmit']],
        '#ajax' => $ajax_settings,
        '#name' => $table_id . '_add',
      ];
      $element['add']['more_items'] = [
        '#type' => 'number',
        '#min' => 1,
        '#max' => 100,
        '#default_value' => $element['#add_more'],
        '#field_suffix' => t('more @labels', ['@labels' => $element['#labels']]),
      ];
    }

    $element['#attached']['library'][] = 'webform/webform.element.multiple';

    return $element;
  }

  /**
   * Build a single element header.
   *
   * @param array $element
   *   The element.
   *
   * @return array
   *   A render array containing inputs for an element's header.
   */
  public static function buildElementHeader(array $element) {
    if (empty($element['#header'])) {
      return [
        ['data' => '', 'colspan' => 4],
      ];
    }
    elseif (is_array($element['#header'])) {
      return array_merge([''], $element['#header'], ['', '']);
    }
    elseif (is_string($element['#header'])) {
      return [
        ['data' => $element['#header'], 'colspan' => ($element['#child_keys']) ? count($element['#child_keys']) + 3 : 4],
      ];
    }
    else {
      $header = [];
      $header['_handle_'] = '';
      if ($element['#child_keys']) {
        foreach ($element['#child_keys'] as $child_key) {
          if (self::isHidden($element['#element'][$child_key])) {
            continue;
          }
          $header[$child_key] = (!empty($element['#element'][$child_key]['#title'])) ? $element['#element'][$child_key]['#title'] : '';
        }
      }
      else {
        $header['item'] = (isset($element['#element']['#title'])) ? $element['#element']['#title'] : '';
      }
      $header['weight'] = t('Weight');
      if (empty($element['#cardinality'])) {
        $header['_operations_'] = '';
      }
      return $header;
    }
  }

  /**
   * Build a single element row.
   *
   * @param string $table_id
   *   The element's table id.
   * @param int $row_index
   *   The row index.
   * @param array $element
   *   The element.
   * @param string $default_value
   *   The default value.
   * @param int $weight
   *   The weight.
   * @param array $ajax_settings
   *   An array containing AJAX callback settings.
   *
   * @return array
   *   A render array containing inputs for an element's value and weight.
   */
  public static function buildElementRow($table_id, $row_index, array $element, $default_value, $weight, array $ajax_settings) {
    if ($element['#child_keys']) {
      foreach ($element['#child_keys'] as $child_key) {
        if (isset($default_value[$child_key])) {
          if ($element['#element'][$child_key]['#type'] == 'value') {
            $element['#element'][$child_key]['#value'] = $default_value[$child_key];
          }
          else {
            $element['#element'][$child_key]['#default_value'] = $default_value[$child_key];
          }
        }
      }
    }
    else {
      $element['#element']['#default_value'] = $default_value;
    }

    $row = [];

    $row['_handle_'] = [];

    if ($element['#child_keys'] && !empty($element['#header'])) {
      foreach ($element['#child_keys'] as $child_key) {
        // Store hidden element in the '_handle_' column.
        // @see \Drupal\webform\Element\WebformMultiple::convertValuesToItems
        if (self::isHidden($element['#element'][$child_key])) {
          $row['_handle_'][$child_key] = $element['#element'][$child_key];
          // ISSUE: All elements in _handle_ are losing their value.
          // WORKAROUND: Convert to element to rendered hidden field.
          $row['_handle_'][$child_key]['#type'] = 'hidden';
          unset($row['_handle_'][$child_key]['#access']);
        }
        else {
          $row[$child_key] = $element['#element'][$child_key];
        }
      }
    }
    else {
      $row['_item_'] = $element['#element'];
    }

    $row['weight'] = [
      '#type' => 'weight',
      '#delta' => 1000,
      '#title' => t('Item weight'),
      '#title_display' => 'invisible',
      '#attributes' => [
        'class' => ['webform-multiple-sort-weight'],
      ],
      '#default_value' => $weight,
    ];

    // Allow users to add & remove rows if cardinality is not set.
    if (empty($element['#cardinality'])) {
      $row['_operations_'] = [];
      $row['_operations_']['add'] = [
        '#type' => 'image_button',
        '#src' => drupal_get_path('module', 'webform') . '/images/icons/plus.svg',
        '#limit_validation_errors' => [],
        '#submit' => [[get_called_class(), 'addItemSubmit']],
        '#ajax' => $ajax_settings,
        // Issue #1342066 Document that buttons with the same #value need a unique
        // #name for the Form API to distinguish them, or change the Form API to
        // assign unique #names automatically.
        '#row_index' => $row_index,
        '#name' => $table_id . '_add_' . $row_index,
      ];
      $row['_operations_']['remove'] = [
        '#type' => 'image_button',
        '#src' => drupal_get_path('module', 'webform') . '/images/icons/ex.svg',
        '#limit_validation_errors' => [],
        '#submit' => [[get_called_class(), 'removeItemSubmit']],
        '#ajax' => $ajax_settings,
        // Issue #1342066 Document that buttons with the same #value need a unique
        // #name for the Form API to distinguish them, or change the Form API to
        // assign unique #names automatically.
        '#row_index' => $row_index,
        '#name' => $table_id . '_remove_' . $row_index,
      ];

      // Bootstrap theme does not support image buttons so we are going to use
      // Boostrap's icon buttons.
      // @see themes/bootstrap/templates/input/input--button.html.twig
      if (WebformThemeHelper::isActiveTheme('bootstrap')) {
        $row['_operations_']['add'] += [
          '#title' => t('Add'),
          '#icon_only' => TRUE,
          '#icon' => \Drupal\bootstrap\Bootstrap::glyphicon('plus-sign'),
        ];
        $row['_operations_']['remove'] += [
          '#title' => t('Remove'),
          '#icon_only' => TRUE,
          '#icon' => \Drupal\bootstrap\Bootstrap::glyphicon('minus-sign'),
        ];
      }
    }

    $row['#weight'] = $weight;
    $row['#attributes']['class'][] = 'draggable';
    return $row;
  }

  /**
   * Determine if an element is hidden.
   *
   * @param array $element
   *   The element.
   *
   * @return bool
   *   TRUE if the element is hidden.
   */
  protected static function isHidden(array $element) {
    if (isset($element['#access']) && $element['#access'] === FALSE) {
      return TRUE;
    }
    elseif (isset($element['#type']) && in_array($element['#type'], ['hidden', 'value'])) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /****************************************************************************/
  // Callbacks.
  /****************************************************************************/

  /**
   * Webform submission handler for adding more items.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function addItemsSubmit(array &$form, FormStateInterface $form_state) {
    // Get the webform list element by going up two levels.
    $button = $form_state->getTriggeringElement();
    $element =& NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));

    // Add more items to the number of items.
    $number_of_items_storage_key = self::getStorageKey($element, 'number_of_items');
    $number_of_items = $form_state->get($number_of_items_storage_key);
    $more_items = (int) $element['add']['more_items']['#value'];
    $form_state->set($number_of_items_storage_key, $number_of_items + $more_items);

    // Reset values.
    $element['items']['#value'] = array_values($element['items']['#value']);
    $form_state->setValueForElement($element['items'], $element['items']['#value']);
    NestedArray::setValue($form_state->getUserInput(), $element['items']['#parents'], $element['items']['#value']);

    // Rebuild the webform.
    $form_state->setRebuild();
  }

  /**
   * Webform submission handler for adding an item.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function addItemSubmit(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -4));

    // Add item.
    $values = [];
    foreach ($element['items']['#value'] as $row_index => $value) {
      $values[] = $value;
      if ($row_index == $button['#row_index']) {
        $values[] = ['item' => '', 'text' => ''];
      }
    }

    // Add one item to the 'number of items'.
    $number_of_items_storage_key = self::getStorageKey($element, 'number_of_items');
    $number_of_items = $form_state->get($number_of_items_storage_key);
    $form_state->set($number_of_items_storage_key, $number_of_items + 1);

    // Reset values.
    $form_state->setValueForElement($element['items'], $values);
    NestedArray::setValue($form_state->getUserInput(), $element['items']['#parents'], $values);

    // Rebuild the webform.
    $form_state->setRebuild();
  }

  /**
   * Webform submission handler for removing an item.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function removeItemSubmit(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -4));
    $values = $element['items']['#value'];

    // Remove item.
    unset($values[$button['#row_index']]);
    $values = array_values($values);

    // Remove one item from the 'number of items'.
    $number_of_items_storage_key = self::getStorageKey($element, 'number_of_items');
    $number_of_items = $form_state->get($number_of_items_storage_key);
    // Never allow the number of items to be less than 1.
    if ($number_of_items != 1) {
      $form_state->set($number_of_items_storage_key, $number_of_items - 1);
    }

    // Reset values.
    $form_state->setValueForElement($element['items'], $values);
    NestedArray::setValue($form_state->getUserInput(), $element['items']['#parents'], $values);

    // Rebuild the webform.
    $form_state->setRebuild();
  }

  /**
   * Webform submission AJAX callback the returns the list table.
   */
  public static function ajaxCallback(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $parent_length = (isset($button['#row_index'])) ? -4 : -2;
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, $parent_length));
    return $element['items'];
  }

  /**
   * Validates webform list element.
   */
  public static function validateWebformMultiple(&$element, FormStateInterface $form_state, &$complete_form) {
    // IMPORTANT: Must get values from the $form_states since sub-elements
    // may call $form_state->setValueForElement() via their validation hook.
    // @see \Drupal\webform\Element\WebformEmailConfirm::validateWebformEmailConfirm
    // @see \Drupal\webform\Element\WebformOtherBase::validateWebformOther
    $values = NestedArray::getValue($form_state->getValues(), $element['#parents']);
    // Convert values to items and validate duplicate keys.
    try {
      $items = self::convertValuesToItems($element, $values['items']);
    }
    catch (\Exception $exception) {
      $form_state->setError($element, new FormattableMarkup($exception->getMessage(), []));
      return;
    }

    // Validate required items.
    if (!empty($element['#required']) && empty($items)) {
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

    $form_state->setValueForElement($element, $items);
  }

  /****************************************************************************/
  // Helper functions.
  /****************************************************************************/

  /**
   * Get unique key used to store the number of items for an element.
   *
   * @param array $element
   *   An element.
   * @param string $name
   *   The storage key's name.
   *
   * @return string
   *   A unique key used to store the number of items for an element.
   */
  public static function getStorageKey(array $element, $name) {
    return 'webform_multiple__' . $element['#name'] . '__' . $name;
  }

  /**
   * Convert an array containing of values (elements or _item_ and weight) to an array of items.
   *
   * @param array $element
   *   The multiple element.
   * @param array $values
   *   An array containing of item and weight.
   *
   * @return array
   *   An array of items.
   *
   * @throws \Exception
   *   Throws unique key required validation error message as an exception.
   */
  public static function convertValuesToItems(array $element, array $values = []) {
    // Sort the item values.
    uasort($values, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);

    // Now build the associative array of items.
    $items = [];
    foreach ($values as $index => $value) {
      $item = NULL;
      if (isset($value['_item_'])) {
        $item = $value['_item_'];
      }
      else {
        // Get hidden (#access: FALSE) elements in the '_handle_' column and
        // add them to the $value.
        // @see \Drupal\webform\Element\WebformMultiple::buildElementRow
        if (isset($value['_handle_']) && is_array($value['_handle_'])) {
          $value += $value['_handle_'];
        }
        unset($value['weight'], $value['_operations_'], $value['_handle_']);
        $item = $value;
      }

      // Never add an empty item.
      if (self::isEmpty($item)) {
        continue;
      }

      // If #key is defined use it as the $items key.
      if (!empty($element['#key']) && isset($item[$element['#key']])) {
        $key_name = $element['#key'];
        $key_value = $item[$key_name];
        unset($item[$key_name]);

        // Validate unique #key.
        if (isset($items[$key_value])) {
          $key_title = isset($element['#element'][$key_name]['#title']) ? $element['#element'][$key_name]['#title'] : $key_name;
          throw new \Exception(t("The %title '@key' is already in use. It must be unique.", ['@key' => $key_value, '%title' => $key_title]));
        }

        $items[$key_value] = $item;
      }
      else {
        $items[] = $item;
      }
    }

    return $items;
  }

  /**
   * Check if array is empty.
   *
   * @param string|array $value
   *   An item.
   *
   * @return bool
   *   FALSE if item is an empty string or an empty array.
   */
  public static function isEmpty($value = NULL) {
    if (is_null($value)) {
      return TRUE;
    }
    elseif (is_string($value)) {
      return ($value === '') ? TRUE : FALSE;
    }
    elseif (is_array($value)) {
      return !array_filter($value, function ($item) {
        return !self::isEmpty($item);
      });
    }
    else {
      return FALSE;
    }
  }

}
