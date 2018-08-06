<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformElementBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a base 'date' class.
 */
abstract class DateBase extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      'multiple' => FALSE,
      'multiple__header_label' => '',
      // Form validation.
      'min' => '',
      'max' => '',
    ] + parent::getDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission) {
    // Don't used 'datetime_wrapper', instead use 'form_element' wrapper.
    // Note: Below code must be executed before parent::prepare().
    // @see \Drupal\Core\Datetime\Element\Datelist
    // @see \Drupal\webform\Plugin\WebformElement\DateTime
    $element['#theme_wrappers'] = ['form_element'];

    // Must manually process #states.
    // @see drupal_process_states().
    if (isset($element['#states'])) {
      $element['#attached']['library'][] = 'core/drupal.states';
      $element['#wrapper_attributes']['data-drupal-states'] = Json::encode($element['#states']);
    }

    parent::prepare($element, $webform_submission);

    // Parse #default_value date input format.
    $this->parseInputFormat($element, '#default_value');

    // Parse #min and #max date input format.
    $this->parseInputFormat($element, '#min');
    $this->parseInputFormat($element, '#max');

    // Override min/max attributes.
    if (!empty($element['#min'])) {
      $element['#attributes']['min'] = $element['#min'];
    }
    if (!empty($element['#max'])) {
      $element['#attributes']['max'] = $element['#max'];
    }

    $element['#element_validate'] = array_merge([[get_class($this), 'preValidateDate']], $element['#element_validate']);
    $element['#element_validate'][] = [get_class($this), 'validateDate'];
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValue(array &$element) {
    if (in_array($element['#type'], ['datelist', 'datetime'])) {
      if (!empty($element['#default_value'])) {
        if (is_array($element['#default_value'])) {
          foreach ($element['#default_value'] as $key => $value) {
            $element['#default_value'][$key] = ($value) ? DrupalDateTime::createFromTimestamp(strtotime($value)) : NULL;
          }
        }
        elseif (is_string($element['#default_value'])) {
          $element['#default_value'] = ($element['#default_value']) ? DrupalDateTime::createFromTimestamp(strtotime($element['#default_value'])) : NULL;
        }
      }
    }
    else {
      parent::setDefaultValue($element);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formatTextItem(array $element, $value, array $options = []) {
    $timestamp = strtotime($value);
    if (empty($timestamp)) {
      return $value;
    }

    $format = $this->getItemFormat($element) ?: 'html_' . $this->getDateType($element);
    if ($format == 'raw') {
      return $value;
    }
    elseif (DateFormat::load($format)) {
      return \Drupal::service('date.formatter')->format($timestamp, $format);
    }
    else {
      return date($format, $timestamp);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormat(array $element) {
    if (isset($element['#format'])) {
      return $element['#format'];
    }
    else {
      return parent::getItemFormat($element);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return 'fallback';
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    $formats = parent::getItemFormats();
    $date_formats = DateFormat::loadMultiple();
    foreach ($date_formats as $date_format) {
      $formats[$date_format->id()] = $date_format->label();
    }
    return $formats;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportRecord(array $element, $value, array $export_options) {
    $element['#format'] = ($this->getDateType($element) === 'datetime') ? 'Y-m-d H:i:s' : 'Y-m-d';
    return [$this->formatText($element, $value, $export_options)];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Append supported date input format to #default_value description.
    $form['element']['default_value']['#description'] .= '<br />' . $this->t('Accepts any date in any <a href="https://www.gnu.org/software/tar/manual/html_chapter/tar_7.html#Date-input-formats">GNU Date Input Format</a>. Strings such as today, +2 months, and Dec 9 2004 are all valid.');

    // Allow custom date formats to be entered.
    $form['display']['format']['#type'] = 'webform_select_other';
    $form['display']['format']['#other__option_label'] = $this->t('Custom date format...');
    $form['display']['format']['#other__description'] = $this->t('A user-defined date format. See the <a href="http://php.net/manual/function.date.php">PHP manual</a> for available options.');

    $form['date'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Date settings'),
    ];

    $form['date']['min'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Min'),
      '#description' => $this->t('Specifies the minimum date.') . '<br />' . $this->t('Accepts any date in any <a href="https://www.gnu.org/software/tar/manual/html_chapter/tar_7.html#Date-input-formats">GNU Date Input Format</a>. Strings such as today, +2 months, and Dec 9 2004 are all valid.'),
      '#weight' => 10,
    ];
    $form['date']['max'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Max'),
      '#description' => $this->t('Specifies the maximum date.') . '<br />' . $this->t('Accepts any date in any <a href="https://www.gnu.org/software/tar/manual/html_chapter/tar_7.html#Date-input-formats">GNU Date Input Format</a>. Strings such as today, +2 months, and Dec 9 2004 are all valid.'),
      '#weight' => 10,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $properties = $this->getConfigurationFormProperties($form, $form_state);

    // Validate #default_value GNU Date Input Format.
    if (isset($properties['#default_value']) && strtotime($properties['#default_value']) === FALSE) {
      $this->setInputFormatError($form['properties']['element']['default_value'], $form_state);
    }

    // Validate #min and #max GNU Date Input Format.
    $input_formats = ['min', 'max'];
    foreach ($input_formats as $input_format) {
      if (!empty($properties["#$input_format"]) && strtotime($properties["#$input_format"]) === FALSE) {
        $this->setInputFormatError($form['properties']['date'][$input_format], $form_state);
      }
    }

    parent::validateConfigurationForm($form, $form_state);
  }

  /**
   * Get the type of date/time element.
   *
   * @param array $element
   *   An element.
   *
   * @return string
   *   The type of date/time element which be either a 'date' or 'datetime'.
   */
  protected function getDateType(array $element) {
    switch ($element['#type']) {
      case 'datelist':
        return (isset($element['#date_part_order']) && !in_array('hour', $element['#date_part_order'])) ? 'date' : 'datetime';

      case 'datetime':
        return 'datetime';

      case 'date':
      default:
        return 'date';
    }
  }

  /**
   * Parse GNU Date Input Format.
   *
   * @param array $element
   *   An element.
   * @param string $property
   *   The element's date property.
   */
  protected function parseInputFormat(array &$element, $property) {
    if (!isset($element[$property])) {
      return;
    }
    elseif (is_array($element[$property])) {
      foreach ($element[$property] as $key => $value) {
        $timestamp = strtotime($value);
        $element[$property][$key] = ($timestamp) ? \Drupal::service('date.formatter')->format($timestamp, 'html_' . $this->getDateType($element)) : NULL;
      }
    }
    else {
      $timestamp = strtotime($element[$property]);
      $element[$property] = ($timestamp) ? \Drupal::service('date.formatter')->format($timestamp, 'html_' . $this->getDateType($element)) : NULL;
    }
  }

  /**
   * Set GNU input format error.
   *
   * @param array $element
   *   The property element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function setInputFormatError(array $element, FormStateInterface $form_state) {
    $t_args = [
      '@title' => $element['#title'] ?: $element['#key'],
    ];
    $form_state->setError($element, $this->t('The @title could not be interpreted in <a href="https://www.gnu.org/software/tar/manual/html_chapter/tar_7.html#Date-input-formats">GNU Date Input Format</a>.', $t_args));
  }

  /**
   * Webform element pre validation handler for Date elements.
   */
  public static function preValidateDate(&$element, FormStateInterface $form_state, &$complete_form) {
    // ISSUE:
    // When datelist is nested inside a webform_multiple element the $form_state
    // value is not being properly set.
    //
    // WORKAROUND:
    // Set the $form_state datelist value using $element['#value'].
    // @todo: Possible move this validation logic to webform_multiple.
    if (!empty($element['#multiple'])) {
      $values = $form_state->getValues();
      NestedArray::setValue($values, $element['#parents'], $element['#value']);
      $form_state->setValues($values);
    }
  }

  /**
   * Webform element validation handler for date elements.
   *
   * Note that #required is validated by _form_validate() already.
   *
   * @see \Drupal\Core\Render\Element\Number::validateNumber
   */
  public static function validateDate(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = $element['#value'];

    // Convert DrupalDateTime array and object to ISO datetime.
    if (is_array($value)) {
      /** @var \Drupal\Core\Datetime\DrupalDateTime $datetime */
      if ($datetime = $value['object']) {
        $value = $datetime->format('c');
      }
      else {
        $value = '';
      }
      $form_state->setValueForElement($element, $value);
    }

    if ($value === '') {
      return;
    }

    $name = empty($element['#title']) ? $element['#parents'][0] : $element['#title'];

    // Ensure the input is valid date.
    // @see http://stackoverflow.com/questions/10691949/check-if-variable-is-a-valid-date-with-php
    $date = date_parse($value);
    if ($date["error_count"] || !checkdate($date["month"], $date["day"], $date["year"])) {
      $form_state->setError($element, t('%name must be a valid date.', ['%name' => $name]));
    }

    $time = strtotime($value);
    $date_date_format = (!empty($element['#date_date_format'])) ? $element['#date_date_format'] : DateFormat::load('html_date')->getPattern();

    // Ensure that the input is greater than the #min property, if set.
    if (isset($element['#min'])) {
      $min = strtotime($element['#min']);
      if ($time < $min) {
        $form_state->setError($element, t('%name must be on or after %min.', [
          '%name' => $name,
          '%min' => date($date_date_format, $min),
        ]));
      }
    }

    // Ensure that the input is less than the #max property, if set.
    if (isset($element['#max'])) {
      $max = strtotime($element['#max']);
      if ($time > $max) {
        $form_state->setError($element, t('%name must be on or before %max.', [
          '%name' => $name,
          '%max' => date($date_date_format, $max),
        ]));
      }
    }
  }

}
