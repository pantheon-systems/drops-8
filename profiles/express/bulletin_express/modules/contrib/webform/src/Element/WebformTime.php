<?php

namespace Drupal\webform\Element;

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a webform element for time selection.
 *
 * @code
 * $form['time'] = array(
 *   '#type' => 'time',
 *   '#title' => $this->t('Time'),
 *   '#default_value' => '12:00 AM'
 * );
 * @endcode
 *
 * @FormElement("webform_time")
 */
class WebformTime extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#theme' => 'input__time',
      '#process' => [[$class, 'processWebformTime']],
      '#pre_render' => [[$class, 'preRenderWebformTime']],
      '#element_validate' => [[$class, 'validateWebformTime']],
      '#theme_wrappers' => ['form_element'],
      '#time_format' => 'H:i',
      '#size' => 10,
      '#maxlength' => 10,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      // Set default value using GNU PHP date format.
      // @see https://www.gnu.org/software/tar/manual/html_chapter/tar_7.html#Date-input-formats.
      if (!empty($element['#default_value'])) {
        $element['#default_value'] = date('H:i', strtotime($element['#default_value']));
        return $element['#default_value'];
      }
    }

    return $input;
  }

  /**
   * Processes a time webform element.
   *
   * @param array $element
   *   The webform element to process. Properties used:
   *   - #time_format: The time format used in PHP formats.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete webform structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processWebformTime(&$element, FormStateInterface $form_state, &$complete_form) {
    // Attach JS support for the time field, if we can determine which time
    // format should be used.
    if (!empty($element['#time_format'])) {
      $element['#attached']['library'][] = 'webform/webform.element.time';
      $element['#attributes']['data-webform-time-format'] = [$element['#time_format']];
    }
    return $element;
  }

  /**
   * Webform element validation handler for #type 'webform_time'.
   *
   * Note that #required is validated by _form_validate() already.
   */
  public static function validateWebformTime(&$element, FormStateInterface $form_state, &$complete_form) {
    $has_access = (!isset($element['#access']) || $element['#access'] === TRUE);

    $value = $element['#value'];
    if ($value === '') {
      return;
    }

    $time = strtotime($value);
    if ($time === FALSE) {
      if ($has_access) {
        if (isset($element['#title'])) {
          $form_state->setError($element, t('%name must be a valid time.', ['%name' => $element['#title']]));
        }
        else {
          $form_state->setError($element);
        }
      }
      return;
    }

    $name = empty($element['#title']) ? $element['#parents'][0] : $element['#title'];
    $time_format = (!empty($element['#time_format'])) ? $element['#time_format'] : DateFormat::load('html_time')->getPattern();

    // Ensure that the input is greater than the #min property, if set.
    if ($has_access && isset($element['#min'])) {
      $min = strtotime($element['#min']);
      if ($time < $min) {
        $form_state->setError($element, t('%name must be on or after %min.', [
          '%name' => $name,
          '%min' => date($time_format, $min),
        ]));
      }
    }

    // Ensure that the input is less than the #max property, if set.
    if ($has_access && isset($element['#max'])) {
      $max = strtotime($element['#max']);
      if ($time > $max) {
        $form_state->setError($element, t('%name must be on or before %max.', [
          '%name' => $name,
          '%max' => date($time_format, $max),
        ]));
      }
    }

    $form_state->setValueForElement($element, date('H:i:s', $time));
  }

  /**
   * Adds form-specific attributes to a 'date' #type element.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *
   * @return array
   *   The $element with prepared variables ready for #theme 'input__time'.
   */
  public static function preRenderWebformTime(array $element) {
    $element['#attributes']['type'] = 'time';
    Element::setAttributes($element, ['id', 'name', 'type', 'value', 'size', 'min', 'max', 'step']);
    static::setAttributes($element, ['form-time']);
    return $element;
  }

}
