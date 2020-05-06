<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\Table;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\webform\Plugin\WebformElement\TableSelect;

/**
 * Provides a webform element for a sortable table element.
 *
 * @FormElement("webform_table_sort")
 */
class WebformTableSort extends Table {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#js_select' => TRUE,
      '#responsive' => TRUE,
      '#sticky' => FALSE,
      '#pre_render' => [
        [$class, 'preRenderTable'],
        [$class, 'preRenderWebformTableSort'],
      ],
      '#process' => [
        [$class, 'processWebformTableSort'],
      ],
      '#options' => [],
      '#empty' => '',
      '#theme' => 'table__table_sort',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      $value = [];
      $element += ['#default_value' => []];
      foreach ($element['#default_value'] as $key => $flag) {
        if ($flag) {
          $value[$key] = $key;
        }
      }
      return $value;
    }
    else {
      if (is_array($input)) {
        uasort($input, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
        $values = [];
        foreach ($input as $key => $item) {
          if (!empty($item['value'])) {
            $values[$item['value']] = $item['value'];
          }
        }
        return $values;
      }
      else {
        return [];
      }
    }
  }

  /**
   * Prepares a 'webform_table_sort' #type element for rendering.
   *
   * @return array
   *   The processed element.
   */
  public static function preRenderWebformTableSort($element) {
    $rows = [];
    $header = $element['#header'];
    if (!empty($element['#options'])) {
      // Generate a table row for each selectable item in #options.
      foreach (Element::children($element) as $key) {
        $row = [];

        $row['data'] = [];

        // Set the row to be draggable.
        $row['class'] = ['draggable'];
        if (isset($element['#options'][$key]['#attributes'])) {
          $row += $element['#options'][$key]['#attributes'];
        }

        // As table.html.twig only maps header and row columns by order, create
        // the correct order by iterating over the header fields.
        foreach ($element['#header'] as $fieldname => $title) {
          // A row cell can span over multiple headers, which means less row
          // cells than headers could be present.
          if (isset($element['#options'][$key][$fieldname])) {
            // A header can span over multiple cells and in this case the cells
            // are passed in an array. The order of this array determines the
            // order in which they are added.
            if (is_array($element['#options'][$key][$fieldname]) && !isset($element['#options'][$key][$fieldname]['data'])) {
              foreach ($element['#options'][$key][$fieldname] as $cell) {
                $row['data'][] = $cell;
              }
            }
            else {
              $row['data'][] = $element['#options'][$key][$fieldname];
            }
          }
        }

        // Render the weight and hidden value element.
        $weight = [$element[$key]['weight'], $element[$key]['value']];
        $row['data'][] = \Drupal::service('renderer')->render($weight);

        $rows[] = $row;
      }
    }

    // Append weight to header.
    $header[] = t('Weight');

    // Set header and rows.
    $element['#header'] = $header;
    $element['#rows'] = $rows;

    // Attach table sort.
    $element['#attributes']['class'][] = 'js-table-sort';
    $element['#attributes']['class'][] = 'table-sort';

    return $element;
  }

  /**
   * Creates checkbox and weights to populate a 'webform_table_order' table.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   webform_table_order element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete webform structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processWebformTableSort(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = is_array($element['#value']) ? $element['#value'] : [];

    // Add validate callback that extracts the associative array of options.
    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [get_called_class(), 'validateWebformTableSelectOrder']);

    $element['#tree'] = TRUE;

    if (count($element['#options']) > 0) {
      if (!isset($element['#default_value']) || $element['#default_value'] === 0) {
        $element['#default_value'] = [];
      }

      // Place checked options first.
      $options = [];
      foreach ($value as $checked_option_key) {
        if (isset($element['#options'][$checked_option_key])) {
          $options[$checked_option_key] = $element['#options'][$checked_option_key];
          unset($element['#options'][$checked_option_key]);
        }
      }
      $options += $element['#options'];
      $element['#options'] = $options;

      // Set delta and default weight.
      $delta = count($element['#options']);
      $weight = 0;

      foreach ($element['#options'] as $key => $choice) {
        // Do not overwrite manually created children.
        if (!isset($element[$key])) {
          $weight_title = '';
          if ($title = TableSelect::getTableSelectOptionTitle($choice)) {
            $weight_title = new TranslatableMarkup('Weight for @title', ['@title' => $title]);
          }

          $element[$key]['value'] = [
            '#type' => 'hidden',
            '#value' => $key,
          ];
          $element[$key]['weight'] = [
            '#type' => 'weight',
            '#title' => $weight_title,
            '#title_display' => 'invisible',
            '#delta' => $delta,
            '#default_value' => $weight++,
            '#attributes' => [
              'class' => ['table-sort-weight'],
            ],
          ];
        }
      }
    }
    else {
      $element['#value'] = [];
    }

    // Enable tabledrag.
    $element['#tabledrag'] = [
      [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'table-sort-weight',
      ],
    ];

    return $element;
  }

  /**
   * Validates webform_table_other.
   */
  public static function validateWebformTableSelectOrder(&$element, FormStateInterface $form_state, &$complete_form) {
    // Get and sort checked values.
    $checked_values = [];
    foreach (Element::children($element) as $key) {
      if ($element[$key]['value']['#value']) {
        $checked_values[] = [
          'value' => $element[$key]['value']['#value'],
          'weight' => $element[$key]['weight']['#value'],
        ];
      }
    }
    uasort($checked_values, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);

    // Set values.
    $values = [];
    foreach ($checked_values as $item) {
      $values[$item['value']] = $item['value'];
    }

    // Clear the element's value by setting it to NULL.
    $form_state->setValueForElement($element, NULL);

    // Now, set the values as the element's value.
    $element['#value'] = $values;
    $form_state->setValueForElement($element, $values);
  }

}
