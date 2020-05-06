<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Datetime\DateHelper;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\webform\Element\WebformMessage as WebformMessageElement;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\Utility\WebformDateHelper;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformInterface;

/**
 * Provides a base 'date' class.
 */
abstract class DateBase extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      // Form validation.
      'date_date_min' => '',
      'date_date_max' => '',
      'date_days' => ['0', '1', '2', '3', '4', '5', '6'],
    ] + parent::defineDefaultProperties()
      + $this->defineDefaultMultipleProperties();
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
    if (!empty($element['#states'])) {
      $element['#attached']['library'][] = 'core/drupal.states';
      $element['#wrapper_attributes']['data-drupal-states'] = Json::encode($element['#states']);
    }

    parent::prepare($element, $webform_submission);

    // Parse #default_value date input format.
    $this->parseInputFormat($element, '#default_value');

    // Set date min/max attributes.
    // This overrides extra attributes set via Datetime::processDatetime.
    // @see \Drupal\Core\Datetime\Element\Datetime::processDatetime
    if (isset($element['#date_date_format'])) {
      $date_min = $this->getElementProperty($element, 'date_date_min') ?: $this->getElementProperty($element, 'date_min');
      if ($date_min) {
        $element['#attributes']['min'] = static::formatDate($element['#date_date_format'], strtotime($date_min));
        $element['#attributes']['data-min-year'] = static::formatDate('Y', strtotime($date_min));
      }
      $date_max = $this->getElementProperty($element, 'date_date_max') ?: $this->getElementProperty($element, 'date_max');
      if (!empty($date_max)) {
        $element['#attributes']['max'] = static::formatDate($element['#date_date_format'], strtotime($date_max));
        $element['#attributes']['data-max-year'] = static::formatDate('Y', strtotime($date_max));
      }
    }

    // Set date days (of week) attributes.
    if (!empty($element['#date_days'])) {
      $element['#attributes']['data-days'] = implode(',', $element['#date_days']);
    }

    // Display datepicker button.
    if (!empty($element['#datepicker_button']) || !empty($element['#date_date_datepicker_button'])) {
      $element['#attributes']['data-datepicker-button'] = TRUE;
      $element['#attached']['drupalSettings']['webform']['datePicker']['buttonImage'] = base_path() . drupal_get_path('module', 'webform') . '/images/elements/date-calendar.png';
    }

    // Set first day according to admin/config/regional/settings.
    $config = $this->configFactory->get('system.date');
    $element['#attached']['drupalSettings']['webform']['dateFirstDay'] = $config->get('first_day');
    $cacheability = CacheableMetadata::createFromObject($config);
    $cacheability->applyTo($element);

    $element['#attached']['library'][] = 'webform/webform.element.date';

    $element['#after_build'][] = [get_class($this), 'afterBuild'];
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareElementValidateCallbacks(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    $element['#element_validate'] = array_merge([[get_class($this), 'preValidateDate']], $element['#element_validate']);
    $element['#element_validate'][] = [get_class($this), 'validateDate'];
    parent::prepareElementValidateCallbacks($element, $webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValue(array &$element) {
    if ($this->hasMultipleValues($element)) {
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

  /**
   * After build handler for date elements.
   */
  public static function afterBuild(array $element, FormStateInterface $form_state) {
    // Add parent title to sub-elements to child elements which applies to
    // datetime and datelist elements.
    $child_keys = Element::children($element);
    foreach ($child_keys as $child_key) {
      if (isset($element[$child_key]['#title'])) {
        $t_args = [
          '@parent' => $element['#title'],
          '@child' => $element[$child_key]['#title'],
        ];
        $element[$child_key]['#title'] = t('@parent: @child', $t_args);
      }
    }

    // Remove orphaned form label.
    if ($child_keys) {
      $element['#label_attributes']['webform-remove-for-attribute'] = TRUE;
    }

    return $element;
  }

  /****************************************************************************/
  // Display submission value methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    $timestamp = strtotime($value);
    if (empty($timestamp)) {
      return $value;
    }

    $format = $this->getItemFormat($element);
    if ($format === 'raw') {
      return $value;
    }
    elseif (DateFormat::load($format)) {
      return \Drupal::service('date.formatter')->format($timestamp, $format);
    }
    else {
      return static::formatDate($format, $timestamp);
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
    // If a default format is defined update the fallback date formats label.
    // @see \Drupal\webform\Plugin\WebformElementBase::getItemFormat
    $default_format = $this->configFactory->get('webform.settings')->get('format.' . $this->getPluginId() . '.item');
    if ($default_format && isset($date_formats[$default_format])) {
      $formats['fallback'] = $this->t('Default date format (@label)', ['@label' => $date_formats[$default_format]->label()]);
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
    $form['default']['default_value']['#description'] .= '<br /><br />' . $this->t("You may use tokens. Tokens should use the 'html_date' or 'html_datetime' date format. (i.e. @date_format)", ['@date_format' => '[current-user:field_date_of_birth:date:html_date]']);

    // Allow custom date formats to be entered.
    $form['display']['format']['#type'] = 'webform_select_other';
    $form['display']['format']['#other__option_label'] = $this->t('Custom date format…');
    $form['display']['format']['#other__description'] = $this->t('A user-defined date format. See the <a href="http://php.net/manual/function.date.php">PHP manual</a> for available options.');

    $form['date'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Date settings'),
    ];

    // Date min/max validation.
    $form['date']['date_container'] = $this->getFormInlineContainer() + [
      '#weight' => 10,
    ];
    $form['date']['date_container']['date_date_min'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date minimum'),
      '#description' => $this->t('Specifies the minimum date.')
        . ' ' . $this->t('To limit the minimum date to the submission date use the <code>[webform_submission:created:html_date]</code> token.')
        . '<br /><br />'
        . $this->t('Accepts any date in any <a href="https://www.gnu.org/software/tar/manual/html_chapter/tar_7.html#Date-input-formats">GNU Date Input Format</a>. Strings such as today, +2 months, and Dec 9 2004 are all valid.'),
    ];
    $form['date']['date_container']['date_date_max'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date maximum'),
      '#description' => $this->t('Specifies the maximum date.')
        . ' ' . $this->t('To limit the maximum date to the submission date use the <code>[webform_submission:created:html_date]</code> token.')
        . '<br /><br />'
        . $this->t('Accepts any date in any <a href="https://www.gnu.org/software/tar/manual/html_chapter/tar_7.html#Date-input-formats">GNU Date Input Format</a>. Strings such as today, +2 months, and Dec 9 2004 are all valid.'),
    ];

    // Date days of the week validation.
    $form['date']['date_days'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Date days of the week'),
      '#options' => DateHelper::weekDaysAbbr(TRUE),
      '#element_validate' => [['\Drupal\webform\Utility\WebformElementHelper', 'filterValues']],
      '#description' => $this->t('Specifies the day(s) of the week. Please note, the date picker will disable unchecked days of the week.'),
      '#options_display' => 'side_by_side',
      '#required' => TRUE,
      '#weight' => 20,
    ];

    // Date/time min/max validation.
    if ($this->hasProperty('date_date_min')
      && $this->hasProperty('date_time_min')
      && $this->hasProperty('date_min')) {
      $form['validation']['date_min_max_message'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#access' => TRUE,
        '#message_message' => $this->t("'Date/time' minimum or maximum should not be used with 'Date' or 'Time' specific minimum or maximum.") . '<br/>' .
          '<strong>' . $this->t('This can cause unexpected validation errors.') . '</strong>',
        '#message_close' => TRUE,
        '#message_storage' => WebformMessageElement::STORAGE_SESSION,
        '#states' => [
          'visible' => [
            [':input[name="properties[date_date_min]"]' => ['filled' => TRUE]],
            [':input[name="properties[date_date_max]"]' => ['filled' => TRUE]],
            [':input[name="properties[date_time_min]"]' => ['filled' => TRUE]],
            [':input[name="properties[date_time_max]"]' => ['filled' => TRUE]],
          ],
        ],
      ];
    }
    $form['validation']['date_container'] = $this->getFormInlineContainer();
    $form['validation']['date_container']['date_min'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date/time minimum'),
      '#description' => $this->t('Specifies the minimum date/time.')
        . ' ' . $this->t('To limit the minimum date/time to the submission date/time use the <code>[webform_submission:created:html_datetime]</code> token.')
        . '<br /><br />'
        . $this->t('Accepts any date in any <a href="https://www.gnu.org/software/tar/manual/html_chapter/tar_7.html#Date-input-formats">GNU Date/Time Input Format</a>. Strings such as today, +2 months, and Dec 9 2004 10:00 PM are all valid.'),
    ];
    $form['validation']['date_container']['date_max'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date/time maximum'),
      '#description' => $this->t('Specifies the maximum date/time.')
        . ' ' . $this->t('To limit the maximum date/time to the submission date/time use the <code>[webform_submission:created:html_datetime]</code> token.')
        . '<br /><br />'
        . $this->t('Accepts any date in any <a href="https://www.gnu.org/software/tar/manual/html_chapter/tar_7.html#Date-input-formats">GNU Date/Time Input Format</a>. Strings such as today, +2 months, and Dec 9 2004 10:00 PM are all valid.'),
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

    // Validate #*_min and #*_max GNU Date Input Format.
    if (!$this->validateGnuDateInputFormat($properties, '#date_min')) {
      $this->setGnuDateInputFormatError($form['properties']['validation']['date_min'], $form_state);
    }
    if (!$this->validateGnuDateInputFormat($properties, '#date_max')) {
      $this->setGnuDateInputFormatError($form['properties']['validation']['date_max'], $form_state);
    }
    if (!$this->validateGnuDateInputFormat($properties, '#date_date_min')) {
      $this->setGnuDateInputFormatError($form['properties']['date']['date_date_min'], $form_state);
    }
    if (!$this->validateGnuDateInputFormat($properties, '#date_date_max')) {
      $this->setGnuDateInputFormatError($form['properties']['date']['date_date_max'], $form_state);
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
        // Time picker converts all submitted time values to H:i:s format.
        // @see \Drupal\webform\Element\WebformTime::validateWebformTime
        if (isset($element['#date_time_element']) && $element['#date_time_element'] === 'timepicker') {
          $element['#date_time_format'] = 'H:i:s';
        }
        $input = $date_class::valueCallback($element, $input, $form_state);
        $form_state->setValueForElement($element, $input);
        $element['#value'] = $input;
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
    $date_time_format = (!empty($element['#date_time_format'])) ? $element['#date_time_format'] : DateFormat::load('html_time')->getPattern();

    // Convert DrupalDateTime array and object to ISO datetime.
    if (is_array($value)) {
      $value = ($value['object']) ? $value['object']->format(DateFormat::load('html_datetime')->getPattern()) : '';
    }
    elseif ($value) {
      $datetime = WebformDateHelper::createFromFormat($date_date_format, $value);
      if ($datetime === FALSE || static::formatDate($date_date_format, $datetime->getTimestamp()) !== $value) {
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

    // Ensure that the input is greater than the #date_date_min property, if set.
    if (isset($element['#date_date_min'])) {
      $min = strtotime(static::formatDate('Y-m-d', strtotime($element['#date_date_min'])));
      if ($time < $min) {
        $form_state->setError($element, t('%name must be on or after %min.', [
          '%name' => $name,
          '%min' => static::formatDate($date_date_format, $min),
        ]));
      }
    }

    // Ensure that the input is less than the #date_date_max property, if set.
    if (isset($element['#date_date_max'])) {
      $max = strtotime(static::formatDate('Y-m-d 23:59:59', strtotime($element['#date_date_max'])));
      if ($time > $max) {
        $form_state->setError($element, t('%name must be on or before %max.', [
          '%name' => $name,
          '%max' => static::formatDate($date_date_format, $max),
        ]));
      }
    }

    // Ensure that the input is greater than the #date_min property, if set.
    if (isset($element['#date_min'])) {
      $min = strtotime($element['#date_min']);
      if ($time < $min) {
        $form_state->setError($element, t('%name must be on or after %min.', [
          '%name' => $name,
          '%min' => static::formatDate($date_date_format, $min) . ' ' . static::formatDate($date_time_format, $min),
        ]));
      }
    }

    // Ensure that the input is less than the #date_max property, if set.
    if (isset($element['#date_max'])) {
      $max = strtotime($element['#date_max']);
      if ($time > $max) {
        $form_state->setError($element, t('%name must be on or before %max.', [
          '%name' => $name,
          '%max' => static::formatDate($date_date_format, $max) . ' ' . static::formatDate($date_time_format, $max),
        ]));
      }
    }

    // Ensure that the input is a day of week.
    if (!empty($element['#date_days'])) {
      $days = $element['#date_days'];
      $day = date('w', $time);
      if (!in_array($day, $days)) {
        $form_state->setError($element, t('%name must be a %days.', [
          '%name' => $name,
          '%days' => WebformArrayHelper::toString(array_intersect_key(DateHelper::weekDays(TRUE), array_combine($days, $days)), t('or')),
        ]));
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    $format = DateFormat::load('html_datetime')->getPattern();
    if (!empty($element['#date_year_range'])) {
      list($min, $max) = static::datetimeRangeYears($element['#date_year_range']);
    }
    else {
      $min = !empty($element['#date_date_min']) ? strtotime($element['#date_date_min']) : strtotime('-10 years');
      $max = !empty($element['#date_date_max']) ? strtotime($element['#date_date_max']) : max($min, strtotime('+20 years') ?: PHP_INT_MAX);
    }
    return static::formatDate($format, rand($min, $max));
  }

  /**
   * Specifies the start and end year to use as a date range.
   *
   * Copied from: DateElementBase::datetimeRangeYears.
   *
   * @param string $string
   *   A min and max year string like '-3:+1' or '2000:2010' or '2000:+3'.
   * @param object $date
   *   (optional) A date object to test as a default value. Defaults to NULL.
   *
   * @return array
   *   A numerically indexed array, containing the minimum and maximum year
   *   described by this pattern.
   *
   * @see \Drupal\Core\Datetime\Element\DateElementBase::datetimeRangeYears
   */
  protected static function datetimeRangeYears($string, $date = NULL) {
    $datetime = new DrupalDateTime();
    $this_year = $datetime->format('Y');
    list($min_year, $max_year) = explode(':', $string);

    // Valid patterns would be -5:+5, 0:+1, 2008:2010.
    $plus_pattern = '@[\+|\-][0-9]{1,4}@';
    $year_pattern = '@^[0-9]{4}@';
    if (!preg_match($year_pattern, $min_year, $matches)) {
      if (preg_match($plus_pattern, $min_year, $matches)) {
        $min_year = $this_year + $matches[0];
      }
      else {
        $min_year = $this_year;
      }
    }
    if (!preg_match($year_pattern, $max_year, $matches)) {
      if (preg_match($plus_pattern, $max_year, $matches)) {
        $max_year = $this_year + $matches[0];
      }
      else {
        $max_year = $this_year;
      }
    }
    // We expect the $min year to be less than the $max year. Some custom values
    // for -99:+99 might not obey that.
    if ($min_year > $max_year) {
      $temp = $max_year;
      $max_year = $min_year;
      $min_year = $temp;
    }
    // If there is a current value, stretch the range to include it.
    $value_year = $date instanceof DrupalDateTime ? $date->format('Y') : '';
    if (!empty($value_year)) {
      $min_year = min($value_year, $min_year);
      $max_year = max($value_year, $max_year);
    }
    return [$min_year, $max_year];
  }

  /**
   * Format custom date.
   *
   * @param string $custom_format
   *   A PHP date format string suitable for input to date().
   * @param int $timestamp
   *   (optional) A UNIX timestamp to format.
   *
   * @return string
   *   Formatted date.
   */
  protected static function formatDate($custom_format, $timestamp = NULL) {
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    $date_formatter = \Drupal::service('date.formatter');
    return $date_formatter->format($timestamp ?: time(), 'custom', $custom_format);
  }

}
