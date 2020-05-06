<?php

/**
 * @file
 * Callback functions for date, datetime, and time elements.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\DateHelper;

/**
 * Callback for removing abbreviation from datelist.
 *
 * @param array $element
 *   The element.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The form state.
 * @param \Drupal\Core\Datetime\DrupalDateTime|null $date
 *   The date value.
 *
 * @see \Drupal\Core\Datetime\Element\Datelist::processDatelist
 */
function _webform_datelist_date_date_callback(array &$element, FormStateInterface $form_state, $date) {
  $no_abbreviate = (isset($element['#date_abbreviate']) && $element['#date_abbreviate'] === FALSE);
  if ($no_abbreviate && isset($element['month']) && isset($element['month']['#options'])) {
    // Load translated date part labels from the appropriate calendar plugin.
    $date_helper = new DateHelper();
    $element['month']['#options'] = $date_helper->monthNames($element['#required']);
  }
}

/**
 * Callback for custom datetime date element.
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
function _webform_datetime_date(array &$element, FormStateInterface $form_state, DrupalDateTime $date = NULL) {
  // Make sure the date element is being displayed.
  if (!isset($element['date'])) {
    return;
  }

  $type = (isset($element['#date_date_element'])) ? $element['#date_date_element'] : 'date';
  switch ($type) {
    case 'datepicker':
      // Convert #type from datepicker to textfield.
      $element['date']['#type'] = 'textfield';

      // Must manually set 'data-drupal-date-format' to trigger date picker.
      // @see \Drupal\Core\Render\Element\Date::processDate
      $element['date']['#attributes']['data-drupal-date-format'] = [$element['date']['#date_date_format']];
      break;
  }
}

/**
 * Callback for custom datetime time element.
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
function _webform_datetime_time(array &$element, FormStateInterface $form_state, DrupalDateTime $date = NULL) {
  // Make sure the time element is being displayed.
  if (!isset($element['time'])) {
    return;
  }

  // Apply time specific min/max to the element.
  foreach (['min', 'max'] as $property) {
    if (!empty($element["#date_time_$property"])) {
      $value = $element["#date_time_$property"];
    }
    elseif (!empty($element["#date_$property"])) {
      $value = date('H:i:s', strtotime($element["#date_$property"]));
    }
    else {
      $value = NULL;
    }
    if ($value) {
      $element['time']["#$property"] = $value;
      $element['time']['#attributes'][$property] = $value;
    }
  }

  // Apply time step and format to the element.
  if (!empty($element['#date_time_step'])) {
    $element['time']['#step'] = $element['#date_time_step'];
    $element['time']['#attributes']['step'] = $element['#date_time_step'];
  }
  if (!empty($element['#date_time_format'])) {
    $element['time']['#time_format'] = $element['#date_time_format'];
  }

  // Remove extra attributes for date element.
  unset(
    $element['time']['#attributes']['data-min-year'],
    $element['time']['#attributes']['data-max-year']
  );

  $type = (isset($element['#date_time_element'])) ? $element['#date_time_element'] : 'time';

  switch ($type) {
    case 'timepicker':
      $element['time']['#type'] = 'webform_time';
      $element['time']['#timepicker'] = TRUE;
      break;

    case 'time':
      $element['time']['#type'] = 'webform_time';
      break;

    case 'text':
      $element['time']['#element_validate'][] = ['\Drupal\webform\Element\WebformTime', 'validateWebformTime'];
      break;
  }
}
