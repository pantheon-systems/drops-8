<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a webform element for element multiple property.
 *
 * This element displays the #multiple property so that it looks like
 * the cardinality setting included in the Field API.
 *
 * @FormElement("webform_element_multiple")
 *
 * @see \Drupal\field_ui\Form\FieldStorageConfigEditForm::form
 */
class WebformElementMultiple extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#min' => 1,
      '#process' => [
        [$class, 'processWebformElementMultiple'],
      ],
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      if (empty($element['#default_value'])) {
        return 1;
      }
      elseif ($element['#default_value'] === TRUE) {
        return WebformMultiple::CARDINALITY_UNLIMITED;
      }
      else {
        return $element['#default_value'];
      }
    }

    return NULL;
  }

  /**
   * Processes element multiple.
   */
  public static function processWebformElementMultiple(&$element, FormStateInterface $form_state, &$complete_form) {
    $cardinality = $element['#value'];

    $element['#tree'] = TRUE;

    $element['container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['container-inline'],
      ],
    ];

    $element['container']['cardinality'] = [
      '#type' => 'select',
      '#title' => t('Allowed number of values'),
      '#title_display' => 'invisible',
      '#options' => [
        'number' => t('Limited'),
        WebformMultiple::CARDINALITY_UNLIMITED => t('Unlimited'),
      ],
      '#default_value' => ($cardinality == WebformMultiple::CARDINALITY_UNLIMITED) ? WebformMultiple::CARDINALITY_UNLIMITED : 'number',
    ];
    $element['container']['cardinality_number'] = [
      '#type' => 'number',
      '#default_value' => $cardinality != WebformMultiple::CARDINALITY_UNLIMITED ? $cardinality : $element['#min'],
      '#min' => $element['#min'],
      '#title' => t('Limit'),
      '#title_display' => 'invisible',
      '#size' => 2,
      '#states' => [
        'visible' => [
          ':input[data-drupal-selector="edit-' . implode('-', $element['#parents']) . '-container-cardinality"]' => ['value' => 'number'],
        ],
      ],
    ];

    // Set disabled.
    if (!empty($element['#disabled'])) {
      $element['container']['cardinality']['#disabled'] = TRUE;
      $element['container']['cardinality_number']['#disabled'] = TRUE;
    }

    // Set validation.
    if (isset($element['#element_validate'])) {
      $element['#element_validate'] = array_merge([[get_called_class(), 'validateWebformElementMultiple']], $element['#element_validate']);
    }
    else {
      $element['#element_validate'] = [[get_called_class(), 'validateWebformElementMultiple']];
    }

    // Set #type to item to apply #states.
    // @see drupal_process_states
    $element['#type'] = 'item';

    return $element;
  }

  /**
   * Validates element multiple.
   */
  public static function validateWebformElementMultiple(&$element, FormStateInterface $form_state, &$complete_form) {
    if (!empty($element['#disabled'])) {
      $multiple = $element['#default_value'];
    }
    else {
      $cardinality = $element['#value']['container']['cardinality'];
      $cardinality_number = (int) $element['#value']['container']['cardinality_number'];

      if ($cardinality == WebformMultiple::CARDINALITY_UNLIMITED) {
        $multiple = TRUE;
      }
      elseif ($cardinality_number === 1) {
        $multiple = FALSE;
      }
      else {
        $multiple = $cardinality_number;
      }
    }

    $form_state->setValueForElement($element['container']['cardinality'], NULL);
    $form_state->setValueForElement($element['container']['cardinality_number'], NULL);
    $form_state->setValueForElement($element, $multiple);
  }

}
