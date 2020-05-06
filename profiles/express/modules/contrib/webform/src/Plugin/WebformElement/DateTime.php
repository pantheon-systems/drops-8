<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionConditionsValidator;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'datetime' element.
 *
 * @WebformElement(
 *   id = "datetime",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Datetime!Element!Datetime.php/class/Datetime",
 *   label = @Translation("Date/time"),
 *   description = @Translation("Provides a form element for date & time selection."),
 *   category = @Translation("Date/time elements"),
 * )
 */
class DateTime extends DateBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $date_format = '';
    $time_format = '';

    // Date formats cannot be loaded during install or update.
    if (!defined('MAINTENANCE_MODE')) {
      /** @var \Drupal\Core\Datetime\DateFormatInterface $date_format_entity */
      if ($date_format_entity = DateFormat::load('html_date')) {
        $date_format = $date_format_entity->getPattern();
      }
      /** @var \Drupal\Core\Datetime\DateFormatInterface $time_format_entity */
      if ($time_format_entity = DateFormat::load('html_time')) {
        $time_format = $time_format_entity->getPattern();
      }
    }

    return [
      'date_min' => '',
      'date_max' => '',
      // Date settings.
      'date_date_format' => $date_format,
      'date_date_datepicker_button' => FALSE,
      'date_date_element' => 'date',
      'date_year_range' => '1900:2050',
      'date_date_placeholder' => '',
      // Time settings.
      'date_time_format' => $time_format,
      'date_time_element' => 'time',
      'date_time_min' => '',
      'date_time_max' => '',
      'date_time_step' => '',
      'date_time_placeholder' => '',
    ] + parent::defineDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  protected function defineTranslatableProperties() {
    return array_merge(parent::defineTranslatableProperties(), ['date_date_placeholder', 'date_time_placeholder']);
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    // Must define a '#default_value' for Datetime element to prevent the
    // below error.
    // Notice: Undefined index: #default_value in Drupal\Core\Datetime\Element\Datetime::valueCallback().
    if (!isset($element['#default_value'])) {
      $element['#default_value'] = NULL;
    }

    // Remove 'for' from the element's label.
    $element['#label_attributes']['webform-remove-for-attribute'] = TRUE;

    /* Date */

    $date_element = (isset($element['#date_date_element'])) ? $element['#date_date_element'] : 'date';

    // Unset unsupported date format for date elements that are not
    // text or datepicker.
    if (!in_array($date_element, ['text', 'datepicker'])) {
      unset($element['date_date_format']);
    }

    // Set date year range.
    $element += ['#date_year_range' => ''];
    if (empty($element['#date_year_range'])) {
      $date_min = $this->getElementProperty($element, 'date_date_min') ?: $this->getElementProperty($element, 'date_min');
      $min_year = ($date_min) ? static::formatDate('Y', strtotime($date_min)) : '1900';
      $date_max = $this->getElementProperty($element, 'date_date_max') ?: $this->getElementProperty($element, 'date_max');
      $max_year = ($date_max) ? static::formatDate('Y', strtotime($date_max)) : '2050';
      $element['#date_year_range'] = "$min_year:$max_year";
    }

    // Set date format.
    if (!isset($element['#date_date_format'])) {
      $element['#date_date_format'] = $this->getDefaultProperty('date_date_format');
    }

    // Add date callback.
    $element['#date_date_callbacks'][] = '_webform_datetime_date';

    /* Time */

    // Set time format.
    if (!isset($element['#date_time_format'])) {
      $element['#date_time_format'] = $this->getDefaultProperty('date_time_format');
    }

    // Add time callback.
    $element['#date_time_callbacks'][] = '_webform_datetime_time';

    // Prepare element after date/time formats have been updated.
    parent::prepare($element, $webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  public static function afterBuild(array $element, FormStateInterface $form_state) {
    $element = parent::afterBuild($element, $form_state);
    // Date and time custom placeholder
    if (isset($element['#date_date_placeholder'])) {
      $element['date']['#attributes']['placeholder'] = $element['#date_date_placeholder'];
    }
    if (isset($element['#date_time_placeholder'])) {
      $element['time']['#attributes']['placeholder'] = $element['#date_time_placeholder'];
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function getElementSelectorInputsOptions(array $element) {
    $t_args = ['@title' => $this->getAdminLabel($element)];
    return [
      'date' => (string) $this->t('@title [Date]', $t_args),
      'time' => (string) $this->t('@title [Time]', $t_args),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorInputValue($selector, $trigger, array $element, WebformSubmissionInterface $webform_submission) {
    $value = $this->getRawValue($element, $webform_submission);
    if (empty($value)) {
      return NULL;
    }

    // Get date/time format pattern.
    $input_name = WebformSubmissionConditionsValidator::getSelectorInputName($selector);
    $format = 'html_' . WebformSubmissionConditionsValidator::getInputNameAsArray($input_name, 1);
    $pattern = DateFormat::load($format)->getPattern();

    // Return date/time.
    $date = DrupalDateTime::createFromTimestamp(strtotime($value));
    return $date->format($pattern);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['date']['#description'] = $this->t('Datetime element is designed to have sane defaults so any or all can be omitted.') . ' ' .
      $this->t('Both the date and time components are configurable so they can be output as HTML5 datetime elements or not, as desired.');

    $form['date']['date_date_element'] = [
      '#type' => 'select',
      '#title' => $this->t('Date element'),
      '#options' => [
        'datetime' => $this->t('HTML datetime - Use the HTML5 datetime element type.'),
        'datetime-local' => $this->t('HTML datetime input (localized) - Use the HTML5 datetime-local element type.'),
        'date' => $this->t('HTML date input - Use the HTML5 date element type.'),
        'text' => $this->t('Text input - No HTML5 element, use a normal text field.'),
        'datepicker' => $this->t('Date picker input - Use jQuery date picker with custom date format'),
        'none' => $this->t('None - Do not display a date element'),
      ],
    ];
    $form['date']['date_date_datepicker_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show date picker button'),
      '#description' => $this->t('If checked, date picker will include a calendar button'),
      '#return_value' => TRUE,
      '#states' => [
        'visible' => [
          [':input[name="properties[date_date_element]"]' => ['value' => 'datepicker']],
        ],
      ],
    ];
    $form['date']['date_date_element_datetime_warning'] = [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t('HTML5 datetime elements do not gracefully degrade in older browsers and will be displayed as a plain text field without a date or time picker.'),
      '#access' => TRUE,
      '#states' => [
        'visible' => [
          [':input[name="properties[date_date_element]"]' => ['value' => 'datetime']],
          'or',
          [':input[name="properties[date_date_element]"]' => ['value' => 'datetime-local']],
        ],
      ],
    ];
    $form['date']['date_date_element_none_warning'] = [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t('You should consider using a dedicated Time element, instead of this Date/time element, which will prepend the current date to the submitted time.'),
      '#access' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="properties[date_date_element]"]' => ['value' => 'none'],
        ],
      ],
    ];
    $form['date']['date_date_placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date placeholder'),
      '#description' => $this->t('The placeholder will be shown in the element until the user starts entering a value.'),
      '#states' => [
        'visible' => [
          [':input[name="properties[date_date_element]"]' => ['value' => 'text']],
          'or',
          [':input[name="properties[date_date_element]"]' => ['value' => 'datepicker']],
        ],
      ],
    ];
    $date_format = DateFormat::load('html_date')->getPattern();
    $form['date']['date_date_format'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Date format'),
      '#options' => [
        $date_format => $this->t('HTML date - @format (@date)', ['@format' => $date_format, '@date' => static::formatDate($date_format)]),
        'l, F j, Y' => $this->t('Long date - @format (@date)', ['@format' => 'l, F j, Y', '@date' => static::formatDate('l, F j, Y')]),
        'D, m/d/Y' => $this->t('Medium date - @format (@date)', ['@format' => 'D, m/d/Y', '@date' => static::formatDate('D, m/d/Y')]),
        'm/d/Y' => $this->t('Short date - @format (@date)', ['@format' => 'm/d/Y', '@date' => static::formatDate('m/d/Y')]),
      ],
      '#other__option_label' => $this->t('Custom…'),
      '#other__placeholder' => $this->t('Custom date format…'),
      '#other__description' => $this->t('Enter date format using <a href="http://php.net/manual/en/function.date.php">Date Input Format</a>.'),
      '#states' => [
        'visible' => [
          [':input[name="properties[date_date_element]"]' => ['value' => 'text']],
          'or',
          [':input[name="properties[date_date_element]"]' => ['value' => 'datepicker']],
        ],
      ],
    ];
    $form['date']['date_year_range'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date year range'),
      '#description' => $this->t("A description of the range of years to allow, like '1900:2050', '-3:+3' or '2000:+3', where the first value describes the earliest year and the second the latest year in the range.") . ' ' .
      $this->t('A year in either position means that specific year.') . ' ' .
      $this->t('A +/- value describes a dynamic value that is that many years earlier or later than the current year at the time the webform is displayed.') . ' ' .
      $this->t("Used in jQueryUI (fallback) datepicker year range and HTML5 min/max date settings. Defaults to '1900:2050'.") . ' ' .
      $this->t('Use min/max validation to define a more specific date range.'),
      '#states' => [
        'invisible' => [
          ':input[name="properties[date_date_element]"]' => ['value' => 'none'],
        ],
      ],
    ];

    // Time.
    $form['time'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Time settings'),
    ];
    $form['time']['date_time_element'] = [
      '#type' => 'select',
      '#title' => $this->t('Time element'),
      '#options' => [
        'time' => $this->t('HTML time input - Use a HTML5 time element type.'),
        'text' => $this->t('Text input - No HTML5 element, use a normal text field.'),
        'timepicker' => $this->t('Time picker input - Use jQuery time picker with custom time format'),
        'none' => $this->t('None - Do not display a time element.'),
      ],
      '#states' => [
        'invisible' => [
          [':input[name="properties[date_date_element]"]' => ['value' => 'datetime']],
          'or',
          [':input[name="properties[date_date_element]"]' => ['value' => 'datetime-local']],
        ],
      ],
    ];
    $form['time']['date_time_placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Time placeholder'),
      '#description' => $this->t('The placeholder will be shown in the element until the user starts entering a value.'),
      '#states' => [
        'visible' => [
          [':input[name="properties[date_time_element]"]' => ['value' => 'text']],
          'or',
          [':input[name="properties[date_time_element]"]' => ['value' => 'timepicker']],
        ],
      ],
    ];
    $form['time']['date_time_format'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Time format'),
      '#description' => $this->t("Time format is only applicable for browsers that do not have support for the HTML5 time element. Browsers that support the HTML5 time element will display the time using the user's preferred format."),
      '#options' => [
        'H:i:s' => $this->t('24 hour with seconds - @format (@time)', ['@format' => 'H:i:s', '@time' => static::formatDate('H:i:s')]),
        'H:i' => $this->t('24 hour - @format (@time)', ['@format' => 'H:i', '@time' => static::formatDate('H:i')]),
        'g:i:s A' => $this->t('12 hour with seconds - @format (@time)', ['@format' => 'g:i:s A', '@time' => static::formatDate('g:i:s A')]),
        'g:i A' => $this->t('12 hour - @format (@time)', ['@format' => 'g:i A', '@time' => static::formatDate('g:i A')]),
      ],
      '#other__option_label' => $this->t('Custom…'),
      '#other__placeholder' => $this->t('Custom time format…'),
      '#other__description' => $this->t('Enter time format using <a href="http://php.net/manual/en/function.date.php">Time Input Format</a>.'),
      '#states' => [
        'invisible' => [
          [':input[name="properties[date_date_element]"]' => ['value' => 'datetime']],
          'or',
          [':input[name="properties[date_date_element]"]' => ['value' => 'datetime-local']],
          'or',
          [':input[name="properties[date_time_element]"]' => ['value' => 'none']],
          'or',
          [':input[name="properties[date_time_element]"]' => ['value' => 'time']],
        ],
      ],
    ];
    $form['time']['date_time_container'] = $this->getFormInlineContainer();
    $form['time']['date_time_container']['date_time_min'] = [
      '#type' => 'webform_time',
      '#title' => $this->t('Time minimum'),
      '#description' => $this->t('Specifies the minimum time.'),
      '#states' => [
        'invisible' => [
          [':input[name="properties[date_time_element]"]' => ['value' => 'none']],
        ],
      ],
    ];
    $form['time']['date_time_container']['date_time_max'] = [
      '#type' => 'webform_time',
      '#title' => $this->t('Time maximum'),
      '#description' => $this->t('Specifies the maximum time.'),
      '#states' => [
        'invisible' => [
          [':input[name="properties[date_time_element]"]' => ['value' => 'none']],
        ],
      ],
    ];
    $form['time']['date_time_step'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Time step'),
      '#description' => $this->t('Specifies the minute intervals.'),
      '#options' => [
        60 => $this->t('1 minute'),
        300 => $this->t('5 minutes'),
        600 => $this->t('10 minutes'),
        900 => $this->t('15 minutes'),
        1200 => $this->t('20 minutes'),
        1800 => $this->t('30 minutes'),
      ],
      '#other__type' => 'number',
      '#other__description' => $this->t('Enter interval in seconds.'),
      '#states' => [
        'invisible' => [
          [':input[name="properties[date_time_element]"]' => ['value' => 'none']],
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurationFormProperties(array &$form, FormStateInterface $form_state) {
    $properties = parent::getConfigurationFormProperties($form, $form_state);

    // Remove hidden date properties.
    if (isset($properties['#date_date_element'])) {
      switch ($properties['#date_date_element']) {
        case 'date':
          unset(
            $properties['#date_date_format']
          );
          break;

        case 'datetime':
        case 'datetime-local':
          unset(
            $properties['#date_date_format'],
            $properties['#date_time_element'],
            $properties['#date_time_format'],
            $properties['#date_increment']
          );
          break;

        case 'none':
          unset(
            $properties['#date_date_format'],
            $properties['#date_year_range']
          );
          break;
      }
    }

    // Remove hidden date properties.
    if (isset($properties['#date_time_element'])) {
      switch ($properties['#date_time_element']) {
        case 'time':
          unset(
            $properties['#date_time_format']
          );
          break;

        case 'none':
          unset(
            $properties['#date_time_format'],
            $properties['date_increment']
          );
          break;
      }
    }

    return $properties;
  }

}
