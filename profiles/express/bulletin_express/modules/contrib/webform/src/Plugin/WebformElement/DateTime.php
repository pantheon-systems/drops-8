<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Form\FormStateInterface;
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
  public function getDefaultProperties() {
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

    return parent::getDefaultProperties() + [
      // Date settings.
      'date_date_format' => $date_format,
      'date_date_element' => 'date',
      'date_time_format' => $time_format,
      'date_time_element' => 'time',
      'date_year_range' => '1900:2050',
      'date_increment' => 1,
      'date_timezone' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission) {
    parent::prepare($element, $webform_submission);

    // Must define a '#default_value' for Datetime element to prevent the
    // below error.
    // Notice: Undefined index: #default_value in Drupal\Core\Datetime\Element\Datetime::valueCallback().
    if (!isset($element['#default_value'])) {
      $element['#default_value'] = NULL;
    }

    // Issue #1838234 Add jQuery Timepicker for the Time element of the
    // datetime field.
    $element['#attached']['library'][] = 'webform/webform.element.time';
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
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    // Date.
    $form['date'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Date/time settings'),
      '#description' => $this->t('Datetime element is designed to have sane defaults so any or all can be omitted.') . ' ' .
      $this->t('Both the date and time components are configurable so they can be output as HTML5 datetime elements or not, as desired.'),
    ];
    $form['date']['date_date_element'] = [
      '#type' => 'select',
      '#title' => t('Date element'),
      '#options' => [
        'datetime' => $this->t('HTML datetime - Use the HTML5 datetime element type.'),
        'datetime-local' => $this->t('HTML datetime input (localized) - Use the HTML5 datetime-local element type.'),
        'date' => $this->t('HTML date input - Use the HTML5 date element type.'),
        'text' => $this->t('Text input - No HTML5 element, use a normal text field.'),
        'none' => $this->t('None - Do not display a date element'),
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
      '#message_message' => $this->t('You should consider using a dedicated Time element, instead of this Date/time element, which will preprend the current date to the submitted time.'),
      '#access' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="properties[date_date_element]"]' => ['value' => 'none'],
        ],
      ],
    ];
    $date_format = DateFormat::load('html_date')->getPattern();
    $form['date']['date_date_format'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Date format'),
      '#options' => [
        $date_format => $this->t('Year-Month-Date (@date)', ['@date' => date($date_format)]),
      ],
      '#description' => $this->t("Date format is only applicable for browsers that do not have support for the HTML5 date element. Browsers that support the HTML5 date element will display the date using the user's preferred format."),
      '#other__option_label' => $this->t('Custom...'),
      '#other__placeholder' => $this->t('Custom date format...'),
      '#other__description' => $this->t('Enter date format using <a href="http://php.net/manual/en/function.date.php">Date Input Format</a>.'),
      '#states' => [
        'invisible' => [
          ':input[name="properties[date_date_element]"]' => ['value' => 'none'],
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
    $form['date']['date_time_element'] = [
      '#type' => 'select',
      '#title' => t('Time element'),
      '#options' => [
        'time' => $this->t('HTML time input - Use a HTML5 time element type.'),
        'text' => $this->t('Text input - No HTML5 element, use a normal text field.'),
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
    $form['date']['date_time_format'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Time format'),
      '#description' => $this->t("Time format is only applicable for browsers that do not have support for the HTML5 time element. Browsers that support the HTML5 time element will display the time using the user's preferred format."),
      '#options' => [
        'g:i A' => $this->t('12 hour (@time)', ['@time' => date('g:i A')]),
        'g:i:s A' => $this->t('12 hour with seconds (@time)', ['@time' => date('g:i:s A')]),
        'H:i' => $this->t('24 hour (@time)', ['@time' => date('H:i')]),
        'H:i:s' => $this->t('24 hour with seconds (@time)', ['@time' => date('H:i:s')]),
      ],
      '#other__option_label' => $this->t('Custom...'),
      '#other__placeholder' => $this->t('Custom time format...'),
      '#other__description' => $this->t('Enter time format using <a href="http://php.net/manual/en/function.date.php">Time Input Format</a>.'),
      '#states' => [
        'invisible'  => [
          [':input[name="properties[date_date_element]"]' => ['value' => 'datetime']],
          'or',
          [':input[name="properties[date_date_element]"]' => ['value' => 'datetime-local']],
          'or',
          [':input[name="properties[date_time_element]"]' => ['value' => 'none']],
        ],
      ],
    ];
    $form['date']['date_increment'] = [
      '#type' => 'number',
      '#title' => $this->t('Date increment'),
      '#description' => $this->t("The increment to use for minutes and seconds, i.e. '15' would show only :00, :15, :30 and :45. Used for HTML5 step values and jQueryUI (fallback) datepicker settings. Defaults to 1 to show every minute."),
      '#min' => 1,
      '#states' => [
        'invisible' => [
          [':input[name="properties[date_date_element]"]' => ['value' => 'datetime']],
          'xor',
          [':input[name="properties[date_date_element]"]' => ['value' => 'datetime-local']],
          'xor',
          [':input[name="properties[date_time_element]"]' => ['value' => 'none']],
        ],
      ],
    ];
    $form['date']['date_timezone'] = [
      '#type' => 'select',
      '#title' => $this->t('Date timezone override'),
      '#options' => system_time_zones(TRUE),
      '#description' => $this->t('Generally this should be left empty and it will be set correctly for the user using the webform.') . ' ' .
      $this->t('Useful if the default value is empty to designate a desired timezone for dates created in webform processing.') . ' ' .
      $this->t('If a default date is provided, this value will be ignored, the timezone in the default date takes precedence.') . ' ' .
      $this->t('Defaults to the value returned by drupal_get_user_timezone().'),
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
        case 'datetime':
        case 'datetime-local':
          unset(
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
    if (isset($properties['#date_time_element']) && $properties['#date_time_element'] == 'none') {
      unset(
        $properties['#date_time_format'],
        $properties['date_increment']
      );
    }

    return $properties;
  }

}
