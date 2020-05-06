<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\webform\Utility\WebformAccessibilityHelper;
use Drupal\webform\Utility\WebformElementHelper;

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
      '#access' => TRUE,
      '#key' => NULL,
      '#header' => NULL,
      '#element' => [
        '#type' => 'textfield',
        '#title' => t('Item value'),
        '#title_display' => 'invisible',
        '#placeholder' => t('Enter value…'),
      ],
      '#cardinality' => FALSE,
      '#min_items' => NULL,
      '#no_items_message' => $this->t('No items entered. Please add items below.'),
      '#empty_items' => 1,
      '#add_more' => TRUE,
      '#add_more_items' => 1,
      '#add_more_button_label' => $this->t('Add'),
      '#add_more_input' => TRUE,
      '#add_more_input_label' => $this->t('more items'),
      '#sorting' => TRUE,
      '#operations' => TRUE,
      '#add' => TRUE,
      '#ajax_attributes' => [],
      '#table_attributes' => [],
      '#table_wrapper_attributes' => [],
      '#remove' => TRUE,
      '#process' => [
        [$class, 'processWebformMultiple'],
      ],
      '#theme_wrappers' => ['form_element'],
      // Add '#markup' property to add an 'id' attribute to the form element.
      // @see template_preprocess_form_element()
      '#markup' => '',
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
      elseif (!is_array($element['#default_value'])) {
        return [$element['#default_value']];
      }
      else {
        return $element['#default_value'];
      }
    }
    elseif (is_array($input) && isset($input['items'])) {
      return static::convertValuesToItems($element, $input['items']);
    }
    else {
      return [];
    }
  }

  /**
   * Process items and build multiple elements widget.
   */
  public static function processWebformMultiple(&$element, FormStateInterface $form_state, &$complete_form) {
    // Set tree.
    $element['#tree'] = TRUE;

    // Remove 'for' from the element's label.
    $element['#label_attributes']['webform-remove-for-attribute'] = TRUE;

    // Set min items based on when the element is required.
    if (!isset($element['#min_items']) || $element['#min_items'] === '') {
      $element['#min_items'] = (empty($element['#required'])) ? 0 : 1;
    }

    // Make sure min items does not exceed cardinality.
    if (!empty($element['#cardinality']) && $element['#min_items'] > $element['#cardinality']) {
      $element['#min_items'] = $element['#cardinality'];
    }

    // Make sure empty items does not exceed cardinality.
    if (!empty($element['#cardinality']) && $element['#empty_items'] > $element['#cardinality']) {
      $element['#empty_items'] = $element['#cardinality'];
    }

    // If the number of default values exceeds the min items and has required
    // sub-elements, set empty items to 0.
    if (isset($element['#default_value'])
      && is_array($element['#default_value'])
      && count($element['#default_value']) >= $element['#min_items']
      && (static::hasRequireElement($element['#element']))) {
      $element['#empty_items'] = 0;
    }

    // Add validate callback that extracts the array of items.
    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [get_called_class(), 'validateWebformMultiple']);

    // Wrap this $element in a <div> that handle #states.
    WebformElementHelper::fixStatesWrapper($element);

    // Get unique key used to store the current number of items.
    $number_of_items_storage_key = static::getStorageKey($element, 'number_of_items');

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

      // Make sure number of items is greated than min items.
      $min_items = (int) $element['#min_items'];
      $number_of_items = ($number_of_items < $min_items) ? $min_items : $number_of_items;

      // Make sure number of (default) items does not exceed cardinality.
      if (!empty($element['#cardinality']) && $number_of_items > $element['#cardinality']) {
        $number_of_items = $element['#cardinality'];
      }

      $form_state->set($number_of_items_storage_key, $number_of_items);
    }

    $number_of_items = $form_state->get($number_of_items_storage_key);

    $table_id = implode('_', $element['#parents']) . '_table';

    // Disable add operation when #cardinality is met
    // and make sure to limit the number of items.
    if (!empty($element['#cardinality']) && $number_of_items >= $element['#cardinality']) {
      $element['#add'] = FALSE;
      $number_of_items = $element['#cardinality'];
      $form_state->set($number_of_items_storage_key, $number_of_items);
    }

    // Add wrapper to the element.
    $ajax_attributes = $element['#ajax_attributes'];
    $ajax_attributes['id'] = $table_id;
    $element += ['#prefix' => '', '#suffix' => ''];
    $element['#prefix'] = $element['#prefix'] . '<div' . new Attribute($ajax_attributes) . '>';
    $element['#suffix'] = '</div>' . $element['#suffix'];

    // DEBUG:
    // Disable Ajax callback by commenting out the below callback and wrapper.
    $ajax_settings = [
      'callback' => [get_called_class(), 'ajaxCallback'],
      'wrapper' => $table_id,
      'progress' => ['type' => 'none'],
    ];

    // Initialize, prepare, and finalize sub-elements.
    static::initializeElement($element, $form_state, $complete_form);

    // Build (single) element header.
    $header = static::buildElementHeader($element);

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

    // When adding/removing elements we don't need to set any default values.
    $action_key = static::getStorageKey($element, 'action');
    if ($form_state->get($action_key)) {
      $form_state->set($action_key, FALSE);
      $default_values = [];
    }

    foreach ($default_values as $key => $default_value) {
      // If #key is defined make sure to set default value's key item.
      if (!empty($element['#key']) && !isset($default_value[$element['#key']])) {
        $default_value[$element['#key']] = $key;
      }
      $rows[$row_index] = static::buildElementRow($table_id, $row_index, $element, $default_value, $weight++, $ajax_settings);
      $row_index++;
    }

    while ($row_index < $number_of_items) {
      $rows[$row_index] = static::buildElementRow($table_id, $row_index, $element, NULL, $weight++, $ajax_settings);
      $row_index++;
    }

    // Build table.
    $table_wrapper_attributes = $element['#table_wrapper_attributes'];
    $table_wrapper_attributes['class'][] = 'webform-multiple-table';
    if (count($element['#element']) > 1) {
      $table_wrapper_attributes['class'][] = 'webform-multiple-table-responsive';
    }
    $element['items'] = [
      '#prefix' => '<div' . new Attribute($table_wrapper_attributes) . '>',
      '#suffix' => '</div>',
    ] + $rows;

    // Display table if there are any rows.
    if ($rows) {
      $element['items'] += [
        '#type' => 'table',
        '#header' => $header,
        '#attributes' => $element['#table_attributes'],
      ] + $rows;

      // Add sorting to table.
      if ($element['#sorting']) {
        $element['items']['#tabledrag'] = [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'webform-multiple-sort-weight',
          ],
        ];
      }
    }
    elseif (!empty($element['#no_items_message'])) {
      $element['items'] += [
        '#type' => 'webform_message',
        '#message_message' => $element['#no_items_message'],
        '#message_type' => 'info',
        '#attributes' => ['class' => ['webform-multiple-table--no-items-message']],
      ];
    }

    // Build add more actions.
    if ($element['#add_more'] && (empty($element['#cardinality']) || ($number_of_items < $element['#cardinality']))) {
      $element['add'] = [
        '#prefix' => '<div class="webform-multiple-add js-webform-multiple-add container-inline">',
        '#suffix' => '</div>',
      ];
      $element['add']['submit'] = [
        '#type' => 'submit',
        '#value' => $element['#add_more_button_label'],
        '#limit_validation_errors' => [],
        '#submit' => [[get_called_class(), 'addItemsSubmit']],
        '#ajax' => $ajax_settings,
        '#name' => $table_id . '_add',
      ];
      $max = ($element['#cardinality']) ? $element['#cardinality'] - $number_of_items : 100;
      $element['add']['more_items'] = [
        '#type' => 'number',
        '#title' => $element['#add_more_button_label'] . ' ' . $element['#add_more_input_label'],
        '#title_display' => 'invisible',
        '#min' => 1,
        '#max' => $max,
        '#default_value' => $element['#add_more_items'],
        '#field_suffix' => $element['#add_more_input_label'],
        '#error_no_message' => TRUE,
        '#access' => $element['#add_more_input'],
      ];
    }

    $element['#attached']['library'][] = 'webform/webform.element.multiple';

    return $element;
  }

  /**
   * Initialize element.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   An associative array containing the structure of the form.
   */
  protected static function initializeElement(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // Track element child keys.
    $element['#child_keys'] = Element::children($element['#element']);

    if (!$element['#child_keys']) {
      // Apply multiple element's required/optional #states to the
      // individual element.
      if (isset($element['#_webform_states'])) {
        $element['#element'] += ['#states' => []];
        $element['#element']['#states'] = array_intersect_key(
          WebformElementHelper::getStates($element),
          ['required' => 'required', 'optional' => 'optional']
        );
      }
    }
    else {
      // Initialize, prepare, and finalize composite sub-elements.
      // Get composite element required/options states from visible/hidden states.
      $required_states = WebformElementHelper::getRequiredFromVisibleStates($element);
      static::initializeElementRecursive($element, $form_state, $complete_form, $element['#element'], $required_states);
    }
  }

  /**
   * Initialize, prepare, and finalize composite sub-elements recursively.
   *
   * @param array $element
   *   The main element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   An associative array containing the structure of the form.
   * @param array $sub_elements
   *   The sub element.
   * @param array $required_states
   *   An associative array of required states from the main element's
   *   visible/hidden states.
   */
  protected static function initializeElementRecursive(array $element, FormStateInterface $form_state, array &$complete_form, array &$sub_elements, array $required_states) {
    $child_keys = Element::children($sub_elements);

    // Exit immediate if the sub elements has no children.
    if (!$child_keys) {
      return;
    }

    // Determine if the sub elements are the main element for each table cell.
    $is_root = ($element['#element'] === $sub_elements) ? TRUE : FALSE;

    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');
    foreach ($child_keys as $child_key) {
      $sub_element =& $sub_elements[$child_key];

      $element_plugin = $element_manager->getElementInstance($sub_element);

      // If the element's #access is FALSE, apply it to all sub elements.
      if (isset($element['#access']) && $element['#access'] === FALSE) {
        $sub_element['#access'] = FALSE;
      }

      // If #header and root input then hide the sub element's #title.
      if ($element['#header']
        && ($is_root && $element_plugin->isInput($sub_element))
        && !isset($sub_element['#title_display'])) {
        $sub_element['#title_display'] = 'invisible';
      }

      // Initialize the composite sub-element.
      $element_manager->initializeElement($sub_element);

      // Build the composite sub-element.
      $element_manager->buildElement($sub_element, $complete_form, $form_state);

      // Custom validate required sub-element because they can be hidden
      // via #access or #states.
      // @see \Drupal\webform\Element\WebformCompositeBase::validateWebformComposite
      if ($required_states && !empty($sub_element['#required'])) {
        unset($sub_element['#required']);
        $sub_element['#_required'] = TRUE;
        if (!isset($sub_element['#states'])) {
          $sub_element['#states'] = [];
        }
        $sub_element['#states'] += $required_states;
      }

      if (is_array($sub_element)) {
        static::initializeElementRecursive($element, $form_state, $complete_form, $sub_element, $required_states);
      }
    }
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
  protected static function buildElementHeader(array $element) {
    $table_id = implode('-', $element['#parents']) . '-table';

    $colspan = 0;
    if ($element['#sorting']) {
      $colspan += 3;
    }
    if ($element['#operations']) {
      $colspan += 1;
    }

    if (empty($element['#header'])) {
      return [
        [
          'data' => (!empty($element['#title'])) ? WebformAccessibilityHelper::buildVisuallyHidden($element['#title']) : [],
          'colspan' => ($colspan + 1),
        ],
      ];
    }
    elseif (is_array($element['#header'])) {
      $header = [];

      if ($element['#sorting']) {
        $header[] = [
          'data' => WebformAccessibilityHelper::buildVisuallyHidden(t('Re-order')),
          'class' => ["$table_id--handle", 'webform-multiple-table--handle'],
        ];
      }

      $header = array_merge($header, $element['#header']);

      if ($element['#sorting']) {
        $header[] = [
          'data' => ['#markup' => t('Weight')],
          'class' => ["$table_id--weight", 'webform-multiple-table--weight'],
        ];
      }

      if ($element['#operations']) {
        $header[] = [
          'data' => WebformAccessibilityHelper::buildVisuallyHidden(t('Operations')),
          'class' => ["$table_id--handle", 'webform-multiple-table--operations'],
        ];
      }

      return $header;
    }
    elseif (is_string($element['#header'])) {
      return [
        ['data' => $element['#header'], 'colspan' => ($element['#child_keys']) ? count($element['#child_keys']) + $colspan : $colspan + 1],
      ];
    }
    else {
      $header = [];

      if ($element['#sorting']) {
        $header['_handle_'] = [
          'data' => WebformAccessibilityHelper::buildVisuallyHidden(t('Re-order')),
          'class' => ["$table_id--handle", "webform-multiple-table--handle"],
        ];
      }

      if ($element['#child_keys']) {
        foreach ($element['#child_keys'] as $child_key) {
          if (static::isHidden($element['#element'][$child_key])) {
            continue;
          }

          $child_element = $element['#element'][$child_key];

          // Build element title.
          $header[$child_key] = ['data' => static::buildElementTitle($child_element)];

          // Append label attributes to header.
          if (!empty($child_element['#label_attributes'])) {
            $header[$child_key] += $child_element['#label_attributes'];
          }
          $header[$child_key]['class'][] = "$table_id--$child_key";
          $header[$child_key]['class'][] = "webform-multiple-table--$child_key";

        }
      }
      else {
        $header['item'] = [
          'data' => (isset($element['#element']['#title'])) ? $element['#element']['#title'] : '',
          'class' => ["$table_id--item", "webform-multiple-table--item"],
        ];
      }

      if ($element['#sorting']) {
        $header['weight'] = [
          'data' => t('Weight'),
          'class' => ["$table_id--weight", "webform-multiple-table--weight"],
        ];
      }

      if ($element['#operations']) {
        $header['_operations_'] = [
          'data' => WebformAccessibilityHelper::buildVisuallyHidden(t('Operations')),
          'class' => ["$table_id--operations", "webform-multiple-table--operations"],
        ];
      }

      return $header;
    }
  }

  /**
   * Build an element's title with help.
   *
   * @param array $element
   *   An element.
   *
   * @return array
   *   A render array containing an element's title with help.
   */
  protected static function buildElementTitle(array $element) {
    $title = (!empty($element['#title'])) ? $element['#title'] : '';

    $build = [];
    $build['title'] = [
      '#markup' => $title,
    ];
    if (!empty($element['#required']) || !empty($element['#_required'])) {
      $build['title'] += [
        '#prefix' => '<span class="form-required">',
        '#suffix' => '</span>',
      ];
    }
    if (!empty($element['#help'])) {
      $build['help'] = [
        '#type' => 'webform_help',
        '#help' => $element['#help'],
        '#help_title' => $title,
      ];
    }

    return $build;
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
   *   An array containing Ajax callback settings.
   *
   * @return array
   *   A render array containing inputs for an element's value and weight.
   */
  protected static function buildElementRow($table_id, $row_index, array $element, $default_value, $weight, array $ajax_settings) {
    if ($element['#child_keys']) {
      static::setElementRowDefaultValueRecursive($element['#element'], (array) $default_value);
    }
    else {
      static::setElementDefaultValue($element['#element'], $default_value);
    }

    $hidden_elements = [];
    $row = [];

    if ($element['#sorting']) {
      $row['_handle_'] = [
        '#wrapper_attributes' => [
          'class' => ['webform-multiple-table--handle'],
        ],
      ];
    }

    if ($element['#child_keys'] && !empty($element['#header'])) {
      // Set #parents which is used for nested elements.
      // @see \Drupal\webform\Element\WebformMultiple::setElementRowParentsRecursive
      $parents = array_merge($element['#parents'], ['items', $row_index]);
      $hidden_parents = array_merge($element['#parents'], ['items', $row_index, '_hidden_']);
      foreach ($element['#child_keys'] as $child_key) {
        // Store hidden element in the '_handle_' column.
        // @see \Drupal\webform\Element\WebformMultiple::convertValuesToItems
        if (static::isHidden($element['#element'][$child_key])) {
          $hidden_elements[$child_key] = $element['#element'][$child_key];
          // ISSUE:
          // All elements in _handle_ with #access: FALSE are losing
          // their values.
          //
          // Moving these #access: FALSE and value elements outside of the
          // table does not work. What is even move baffling is manually adding
          // a 'value' element does work.
          //
          // $element['hidden'][$row_index][$child_key] = $element['#element'][$child_key];
          // $element['hidden'][1000]['test'] = ['#type' => 'value', '#value' => 'test'];
          //
          // WORKAROUND:
          // Convert element to rendered hidden element.
          if (!isset($element['#access']) || $element['#access'] !== FALSE) {
            $hidden_elements[$child_key]['#type'] = 'hidden';
            // Unset #access, #element_validate, and #pre_render.
            // @see \Drupal\webform\Plugin\WebformElementBase::prepare()
            // Unset #options to prevent An illegal choice has been detected.
            // @see \Drupal\Core\Form\FormValidator::performRequiredValidation
            unset(
              $hidden_elements[$child_key]['#access'],
              $hidden_elements[$child_key]['#element_validate'],
              $hidden_elements[$child_key]['#pre_render'],
              $hidden_elements[$child_key]['#options']
            );
          }
          static::setElementRowParentsRecursive($hidden_elements[$child_key], $child_key, $hidden_parents);
        }
        else {
          $row[$child_key] = $element['#element'][$child_key];
          static::setElementRowParentsRecursive($row[$child_key], $child_key, $parents);
        }
      }
    }
    else {
      $row['_item_'] = $element['#element'];
    }

    if ($element['#sorting']) {
      $row['weight'] = [
        '#type' => 'weight',
        '#delta' => 1000,
        '#title' => t('Item weight'),
        '#title_display' => 'invisible',
        '#attributes' => [
          'class' => ['webform-multiple-sort-weight'],
        ],
        '#wrapper_attributes' => [
          'class' => ['webform-multiple-table--weight'],
        ],
        '#default_value' => $weight,
      ];
    }

    // Allow users to add & remove rows if cardinality is not set.
    if ($element['#operations']) {
      $row['_operations_'] = [
        '#wrapper_attributes' => [
          'class' => ['webform-multiple-table--operations'],
        ],
      ];
      if ($element['#add'] && $element['#remove']) {
        $row['_operations_']['#wrapper_attributes']['class'][] = 'webform-multiple-table--operations-two';
      }
      if ($element['#add']) {
        $row['_operations_']['add'] = [
          '#type' => 'image_button',
          '#title' => t('Add'),
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
      }
      if ($element['#remove']) {
        $row['_operations_']['remove'] = [
          '#type' => 'image_button',
          '#title' => t('Remove'),
          '#src' => drupal_get_path('module', 'webform') . '/images/icons/minus.svg',
          '#limit_validation_errors' => [],
          '#submit' => [[get_called_class(), 'removeItemSubmit']],
          '#ajax' => $ajax_settings,
          // Issue #1342066 Document that buttons with the same #value need a unique
          // #name for the Form API to distinguish them, or change the Form API to
          // assign unique #names automatically.
          '#row_index' => $row_index,
          '#name' => $table_id . '_remove_' . $row_index,
        ];
      }
    }

    // Add hidden element as a hidden row.
    if ($hidden_elements) {
      $row['_hidden_'] = $hidden_elements + [
        '#wrapper_attributes' => ['style' => 'display: none'],
      ];
    }

    if ($element['#sorting']) {
      $row['#attributes']['class'][] = 'draggable';
      $row['#weight'] = $weight;
    }

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

  /**
   * Set element row default value recursively.
   *
   * @param array $element
   *   The element.
   * @param array $default_value
   *   The default values.
   */
  protected static function setElementRowDefaultValueRecursive(array &$element, array $default_value) {
    foreach (Element::children($element) as $child_key) {
      if (isset($default_value[$child_key])) {
        static::setElementDefaultValue($element[$child_key], $default_value[$child_key]);
      }
      static::setElementRowDefaultValueRecursive($element[$child_key], $default_value);
    }
  }

  /**
   * Set element row default value recursively.
   *
   * @param array $element
   *   The element.
   * @param mixed $default_value
   *   The default value.
   */
  protected static function setElementDefaultValue(array &$element, $default_value) {
    if ($element['#type'] == 'value') {
      $element['#value'] = $default_value;
    }
    else {
      $element['#default_value'] = $default_value;
      // Set default value.
      // @see \Drupal\webform\Plugin\WebformElementInterface::setDefaultValue
      // @see \Drupal\webform\Plugin\WebformElement\DateBase::setDefaultValue
      /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
      $element_manager = \Drupal::service('plugin.manager.webform.element');
      $element_plugin = $element_manager->getElementInstance($element);
      $element_plugin->setDefaultValue($element);
    }
  }

  /**
   * Set element row parents recursively.
   *
   * This allow elements/columns to contain nested sub-elements.
   *
   * @param array $element
   *   The child element.
   * @param string $element_key
   *   The child element's key.
   * @param array $parents
   *   The main element's parents.
   */
  protected static function setElementRowParentsRecursive(array &$element, $element_key, array $parents) {
    $element['#parents'] = array_merge($parents, [$element_key]);
    foreach (Element::children($element) as $child_key) {
      static::setElementRowParentsRecursive($element[$child_key], $child_key, $parents);
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
    $number_of_items_storage_key = static::getStorageKey($element, 'number_of_items');
    $number_of_items = $form_state->get($number_of_items_storage_key);
    $more_items = (int) $element['add']['more_items']['#value'];
    $form_state->set($number_of_items_storage_key, $number_of_items + $more_items);

    // Reset values.
    $items = (!empty($element['items']['#value'])) ? array_values($element['items']['#value']) : [];
    $element['items']['#value'] = $items;
    $form_state->setValueForElement($element['items'], $items);
    NestedArray::setValue($form_state->getUserInput(), $element['items']['#parents'], $items);

    $action_key = static::getStorageKey($element, 'action');
    $form_state->set($action_key, TRUE);

    // Rebuild the form.
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
        $values[] = [];
      }
    }

    // Add one item to the 'number of items'.
    $number_of_items_storage_key = static::getStorageKey($element, 'number_of_items');
    $number_of_items = $form_state->get($number_of_items_storage_key);
    $form_state->set($number_of_items_storage_key, $number_of_items + 1);

    // Reset values.
    $form_state->setValueForElement($element['items'], $values);
    NestedArray::setValue($form_state->getUserInput(), $element['items']['#parents'], $values);

    $action_key = static::getStorageKey($element, 'action');
    $form_state->set($action_key, TRUE);

    // Rebuild the form.
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
    $number_of_items_storage_key = static::getStorageKey($element, 'number_of_items');
    $number_of_items = $form_state->get($number_of_items_storage_key);
    // Never allow the number of items to be less than #min_items.
    if ($number_of_items > $element['#min_items']) {
      $form_state->set($number_of_items_storage_key, $number_of_items - 1);
    }

    // Reset values.
    $form_state->setValueForElement($element['items'], $values);
    NestedArray::setValue($form_state->getUserInput(), $element['items']['#parents'], $values);

    $action_key = static::getStorageKey($element, 'action');
    $form_state->set($action_key, TRUE);

    // Rebuild the form.
    $form_state->setRebuild();
  }

  /**
   * Webform submission Ajax callback the returns the list table.
   */
  public static function ajaxCallback(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $parent_length = (isset($button['#row_index'])) ? -4 : -2;
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, $parent_length));
    return $element;
  }

  /**
   * Validates webform multiple element.
   */
  public static function validateWebformMultiple(&$element, FormStateInterface $form_state, &$complete_form) {
    // IMPORTANT: Must get values from the $form_states since sub-elements
    // may call $form_state->setValueForElement() via their validation hook.
    // @see \Drupal\webform\Element\WebformEmailConfirm::validateWebformEmailConfirm
    // @see \Drupal\webform\Element\WebformOtherBase::validateWebformOther
    $values = NestedArray::getValue($form_state->getValues(), $element['#parents']);

    $number_of_items_storage_key = static::getStorageKey($element, 'number_of_items');
    $number_of_items = $form_state->get($number_of_items_storage_key);
    if (!empty($values['items']) && ($number_of_items || $element['#cardinality'])) {
      $items = $values['items'];

      // Validate unique keys.
      if ($error_message = static::validateUniqueKeys($element, $items)) {
        $form_state->setError($element, $error_message);
        return;
      }

      // Convert values to items and validate duplicate keys.
      $items = static::convertValuesToItems($element, $items);

      // Validate required items.
      if (!empty($element['#required']) && empty($items)) {
        WebformElementHelper::setRequiredError($element, $form_state);
      }
    }
    else {
      $items = [];
    }

    $element['#value'] = $items;
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
    if ($element['#sorting']) {
      uasort($values, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
    }

    // Now build the associative array of items.
    $items = [];
    foreach ($values as $value) {
      $item = static::convertValueToItem($value);

      // Never add an empty item.
      if (static::isEmpty($item)) {
        continue;
      }

      // If #key is defined use it as the $items key.
      if (!empty($element['#key']) && isset($item[$element['#key']])) {
        $key_name = $element['#key'];
        $key_value = $item[$key_name];
        unset($item[$key_name]);
        $items[$key_value] = $item;
      }
      else {
        $items[] = $item;
      }
    }

    return $items;
  }

  /**
   * Convert value array containing (elements or _item_ and weight) to an item.
   *
   * @param array $value
   *   The multiple value array.
   *
   * @return array
   *   An item array.
   */
  public static function convertValueToItem(array $value) {
    if (isset($value['_item_'])) {
      return $value['_item_'];
    }
    else {
      // Get hidden (#access: FALSE) elements in the '_handle_' column and
      // add them to the $value.
      // @see \Drupal\webform\Element\WebformMultiple::buildElementRow
      if (isset($value['_hidden_']) && is_array($value['_hidden_'])) {
        $value += $value['_hidden_'];
      }
      unset($value['weight'], $value['_operations_'], $value['_hidden_']);
      return $value;
    }
  }

  /**
   * Validate composite element has unique keys.
   *
   * @param array $element
   *   The multiple element.
   * @param array $values
   *   An array containing of item and weight.
   *
   * @return null|string
   *   NULL if element has unique keys, else an error message with
   *   the duplicate key.
   */
  protected static function validateUniqueKeys(array $element, array $values) {
    // Only validate if the element's #key is defined.
    if (!isset($element['#key'])) {
      return NULL;
    }

    $unique_keys = [];
    foreach ($values as $value) {
      $item = static::convertValueToItem($value);

      $key_name = $element['#key'];
      $key_value = $item[$key_name];

      // Skip empty key and item.
      unset($item[$key_name]);
      if (empty($key_value) && static::isEmpty($item)) {
        continue;
      }

      if (isset($unique_keys[$key_value])) {
        $elements = WebformElementHelper::getFlattened($element['#element']);
        $key_title = isset($elements[$key_name]['#title']) ? $elements[$key_name]['#title'] : $key_name;
        $t_args = ['@key' => $key_value, '%title' => $key_title];
        return t("The %title '@key' is already in use. It must be unique.", $t_args);
      }

      $unique_keys[$key_value] = $key_value;
    }
    return NULL;
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
        return !static::isEmpty($item);
      });
    }
    else {
      return FALSE;
    }
  }

  /**
   * Determine if any sub-element is required.
   *
   * @param array $element
   *   An element.
   *
   * @return bool
   *   TRUE if any sub-element is required.
   */
  protected static function hasRequireElement(array $element) {
    $required_properties = [
      '#required' => TRUE,
      '#_required' => TRUE,
    ];
    return WebformElementHelper::hasProperties($element, $required_properties);
  }

}
