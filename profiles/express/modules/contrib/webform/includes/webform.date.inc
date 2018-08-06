<?php

/**
 * @file
 * Callback functions for date, datetime, and time elements.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Callback for custom datetime datepicker.
 *
 * @param array $element
 *   The element.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The form state.
 * @param \Drupal\Core\Datetime\DrupalDateTime|null $date
 *   The date value.
 *
 * @see \Drupal\webform\Plugin\WebformElement\DateTime::prepare
 */
function _webform_datetime_datepicker(array &$element, FormStateInterface $form_state, DrupalDateTime $date = NULL) {
  // Convert #type from datepicker to textfield.
  if (isset($element['#date_date_element']) && $element['#date_date_element'] === 'datepicker') {
    $element['date']['#type'] = 'textfield';

    // Must manually set 'data-drupal-date-format' to trigger date picker.
    // @see \Drupal\Core\Render\Element\Date::processDate
    $element['date']['#attributes']['data-drupal-date-format'] = [$element['date']['#date_date_format']];
  }

  // Attached custom datepicker library.
  $element['date']['#attached']['library'][] = 'webform/webform.element.date';
}

/**
 * Callback for custom datetime timepicker.
 *
 * @param array $element
 *   The element.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The form state.
 * @param \Drupal\Core\Datetime\DrupalDateTime|null $date
 *   The date value.
 *
 * @see \Drupal\webform\Plugin\WebformElement\DateTime::prepare
 */
function _webform_datetime_timepicker(array &$element, FormStateInterface $form_state, DrupalDateTime $date = NULL) {
  // Convert #type from timepicker to textfield.
  if (isset($element['#date_time_element']) && $element['#date_time_element'] === 'timepicker') {
    $element['time']['#type'] = 'textfield';
  }

  // Must manually set 'data-webform-time-format' to trigger time picker.
  // @see \Drupal\webform\Element\WebformTime::processWebformTime
  $element['time']['#attributes']['data-webform-time-format'] = [$element['#date_time_format']];

  // Attached custom timepicker library.
  $element['time']['#attached']['library'][] = 'webform/webform.element.time';
}
