<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElementBase;
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
      // Form validation.
      'min' => '',
      'max' => '',
    ] + parent::getDefaultProperties() + $this->getDefaultMultipleProperties();
  }

  /****************************************************************************/
  // Element rendering methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
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

    // Override min/max attributes.
    if (isset($element['#date_date_format'])) {
      if (!empty($element['#min'])) {
        $element['#attributes']['min'] = date($element['#date_date_format'], strtotime($element['#min']));
        $element['#attributes']['data-min-year'] = date('Y', strtotime($element['#min']));
      }
      if (!empty($element['#max'])) {
        $element['#attributes']['max'] = date($element['#date_date_format'], strtotime($element['#max']));
        $element['#attributes']['data-max-year'] = date('Y', strtotime($element['#max']));
      }
    }

    $element['#element_validate'] = array_merge([[get_class($this), 'preValidateDate']], $element['#element_validate']);
    $element['#element_validate'][] = [get_class($this), 'validateDate'];
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValue(array &$element) {
    if (isset($element['#multiple'])) {
      $element['#default_value'] = (isset($element['#default_value'])) ? (array) $element['#default_value'] : NULL;
      return;
    }

    // Datelist and Datetime require #default_value to be DrupalDateTime.
    if (in_array($element['#type'], ['datelist', 'datetime'])) {
      if (!empty($element['#default_value']) && is_string($element['#default_value'])) {
        $element['#default_value'] = ($element['#default_value']) ? DrupalDateTime::createFromTimestamp(strtotime($element['#default_value'])) : NULL;
      }
    }
  }

  /****************************************************************************/
  // Display submission value methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

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

  /****************************************************************************/
  // Export methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function buildExportRecord(array $element, WebformSubmissionInterface $webform_submission, array $export_options) {
    $element['#format'] = ($this->getDateType($element) === 'datetime') ? 'Y-m-d H:i:s' : 'Y-m-d';
    return [$this->formatText($element, $webform_submission, $export_options)];
  }

  /****************************************************************************/
  // Element configuration methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Append supported date input format to #default_value description.
    $form['default']['default_value']['#description'] .= '<br /><br />' . $this->t('Accepts any date in any <a href="https://www.gnu.org/software/tar/manual/html_chapter/tar_7.html#Date-input-formats">GNU Date Input Format</a>. Strings such as today, +2 months, and Dec 9 2004 are all valid.');

    // Append token date format to #default_value description.
    $form['default']['default_value']['#description'] .= '<br /><br />' . $this->t("You may use tokens. Tokens should use the 'html_date' or 'html_datetime' date format. (i.e. @date_format)", ['@date_format' => '[webform-authenticated-user:field_date_of_birth:date:html_date]']);

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
      '#title' => $this->t('Date min'),
      '#description' => $this->t('Specifies the minimum date.') . '<br /><br />' . $this->t('Accepts any date in any <a href="https://www.gnu.org/software/tar/manual/html_chapter/tar_7.html#Date-input-formats">GNU Date Input Format</a>. Strings such as today, +2 months, and Dec 9 2004 are all valid.'),
      '#weight' => 10,
    ];
    $form['date']['max'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date max'),
      '#description' => $this->t('Specifies the maximum date.') . '<br /><br />' . $this->t('Accepts any date in any <a href="https://www.gnu.org/software/tar/manual/html_chapter/tar_7.html#Date-input-formats">GNU Date Input Format</a>. Strings such as today, +2 months, and Dec 9 2004 are all valid.'),
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
    if (!$this->validateGnuDateInputFormat($properties, '#default_value')) {
      $this->setGnuDateInputFormatError($form['properties']['default']['default_value'], $form_state);
    }

    // Validate #min and #max GNU Date Input Format.
    $input_formats = ['min', 'max'];
    foreach ($input_formats as $input_format) {
      if (!$this->validateGnuDateInputFormat($properties, "#$input_format")) {
        $this->setGnuDateInputFormatError($form['properties']['date'][$input_format], $form_state);
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

  /****************************************************************************/
  // Validation methods.
  /****************************************************************************/

  /**
   * Validate GNU date input format.
   *
   * @param array $properties
   *   An array of element properties.
   * @param string $key
   *   The property name containing the GNU date input format.
   *
   * @return bool
   *   TRUE if property's value is a valid GNU date input format or contains
   *   a token.
   */
  protected function validateGnuDateInputFormat(array $properties, $key) {
    if (empty($properties[$key])) {
      return TRUE;
    }

    $values = (array) $properties[$key];
    foreach ($values as $value) {
      if (!preg_match('/^\[[^]]+\]$/', $value)) {
        if (strtotime($value) === FALSE) {
          return FALSE;
        }
      }
    }

    return TRUE;
  }

  /**
   * Set GNU date input format error.
   *
   * @param array $element
   *   The property element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function setGnuDateInputFormatError(array $element, FormStateInterface $form_state) {
    $t_args = [
      '@title' => $element['#title'] ?: $element['#key'],
    ];
    $form_state->setError($element, $this->t('The @title could not be interpreted in <a href="https://www.gnu.org/software/tar/manual/html_chapter/tar_7.html#Date-input-formats">GNU Date Input Format</a>.', $t_args));
  }

  /**
   * Webform element pre validation handler for Date elements.
   */
  public static function preValidateDate(&$element, FormStateInterface $form_state, &$complete_form) {
    // ISSUE #2723159:
    // Datetime form element cannot validate when using a
    // format without seconds.
    // WORKAROUND:
    // Append the second format before the time element is validated.
    //
    // @see \Drupal\Core\Datetime\Element\Datetime::valueCallback
    // @see https://www.drupal.org/node/2723159
    if ($element['#type'] === 'datetime' && $element['#date_time_format'] === 'H:i' && strlen($element['#value']['time']) === 8) {
      $element['#date_time_format'] = 'H:i:s';
    }

    // ISSUE:
    // Date list in composite element is missing the date object.
    //
    // WORKAROUND:
    // Manually set the date object.
    $date_element_types = [
      'datelist' => '\Drupal\Core\Datetime\Element\Datelist',
      'datetime' => '\Drupal\Core\Datetime\Element\Datetime',
    ];

    if (isset($date_element_types[$element['#type']])) {
      $date_class = $date_element_types[$element['#type']];
      $input_exists = FALSE;
      $input = NestedArray::getValue($form_state->getValues(), $element['#parents'], $input_exists);
      if (!isset($input['object'])) {
        $input = $date_class::valueCallback($element, $input, $form_state);
        $form_state->setValueForElement($element, $input);
      }
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
    $name = empty($element['#title']) ? $element['#parents'][0] : $element['#title'];
    $date_date_format = (!empty($element['#date_date_format'])) ? $element['#date_date_format'] : DateFormat::load('html_date')->getPattern();

    // Convert DrupalDateTime array and object to ISO datetime.
    if (is_array($value)) {
      $value = ($value['object']) ? $value['object']->format(DateFormat::load('html_datetime')->getPattern()) : '';
    }
    elseif ($value) {
      // Ensure the input is valid date by creating a date object and comparing
      // formatted date object to the submitted date value.
      $datetime = date_create_from_format($date_date_format, $value);
      if ($datetime === FALSE || date_format($datetime, $date_date_format) != $value) {
        $form_state->setError($element, t('%name must be a valid date.', ['%name' => $name]));
        $value = '';
      }
      else {
        // Clear timestamp to date elements.
        if ($element['#type'] === 'date') {
          $datetime->setTime(0, 0, 0);
          $value = $datetime->format(DateFormat::load('html_date')->getPattern());
        }
        else {
          $value = $datetime->format(DateFormat::load('html_datetime')->getPattern());
        }
      }
    }

    $form_state->setValueForElement($element, $value);
    if ($value === '') {
      return;
    }

    $time = strtotime($value);

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
