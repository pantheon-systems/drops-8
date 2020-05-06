<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'date' element.
 *
 * @WebformElement(
 *   id = "date",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Date.php/class/Date",
 *   label = @Translation("Date"),
 *   description = @Translation("Provides a form element for date selection."),
 *   category = @Translation("Date/time elements"),
 * )
 */
class Date extends DateBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $date_format = '';
    // Date formats cannot be loaded during install or update.
    if (!defined('MAINTENANCE_MODE')) {
      /** @var \Drupal\Core\Datetime\DateFormatInterface $date_format_entity */
      if ($date_format_entity = DateFormat::load('html_date')) {
        $date_format = $date_format_entity->getPattern();
      }
    }

    return [
      // Date settings.
      'datepicker' => FALSE,
      'datepicker_button' => FALSE,
      'date_date_format' => $date_format,
      'placeholder' => '',
      'step' => '',
      'size' => '',
    ] + parent::defineDefaultProperties();
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    // Unset unsupported date format for date elements that are not using a
    // datepicker.
    if (empty($element['#datepicker'])) {
      unset($element['#date_date_format']);
    }

    // Set default date format to HTML date.
    if (!isset($element['#date_date_format'])) {
      $element['#date_date_format'] = $this->getDefaultProperty('date_date_format');
    }

    // Set placeholder attribute.
    if (!empty($element['#placeholder'])) {
      $element['#attributes']['placeholder'] = $element['#placeholder'];
    }

    // Prepare element after date format has been updated.
    parent::prepare($element, $webform_submission);

    // Set the (input) type attribute to 'date'.
    // @see \Drupal\Core\Render\Element\Date::getInfo
    $element['#attributes']['type'] = 'date';

    // Convert date element into textfield with date picker.
    if (!empty($element['#datepicker'])) {
      $element['#attributes']['type'] = 'text';

      // Must manually set 'data-drupal-date-format' to trigger date picker.
      // @see \Drupal\Core\Render\Element\Date::processDate
      $element['#attributes']['data-drupal-date-format'] = [$element['#date_date_format']];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValue(array &$element) {
    parent::setDefaultValue($element);

    // Format date picker default value.
    if (!empty($element['#datepicker'])) {
      if (isset($element['#default_value'])) {
        if ($this->hasMultipleValues($element)) {
          foreach ($element['#default_value'] as $index => $default_value) {
            $element['#default_value'][$index] = static::formatDate($element['#date_date_format'], strtotime($default_value));
          }
        }
        else {
          $element['#default_value'] = static::formatDate($element['#date_date_format'], strtotime($element['#default_value']));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormat(array $element) {
    $format = parent::getItemFormat($element);
    // Drupal's default date fallback includes the time so we need to fallback
    // to the specified or default date only format.
    if ($format === 'fallback') {
      $format = (isset($element['#date_date_format'])) ? $element['#date_date_format'] : $this->getDefaultProperty('date_date_format');
    }
    return $format;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['date']['datepicker'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use date picker'),
      '#description' => $this->t('If checked, the HTML5 date element will be replaced with a <a href="https://jqueryui.com/datepicker/">jQuery UI datepicker</a>'),
      '#return_value' => TRUE,
    ];
    $form['date']['datepicker_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show date picker button'),
      '#description' => $this->t('If checked, date picker will include a calendar button'),
      '#return_value' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="properties[datepicker]"]' => ['checked' => TRUE],
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
      '#description' => $this->t("Date format is only applicable for browsers that do not have support for the HTML5 date element. Browsers that support the HTML5 date element will display the date using the user's preferred format."),
      '#other__option_label' => $this->t('Custom…'),
      '#other__placeholder' => $this->t('Custom date format…'),
      '#other__description' => $this->t('Enter date format using <a href="http://php.net/manual/en/function.date.php">Date Input Format</a>.'),
      '#states' => [
        'visible' => [
          ':input[name="properties[datepicker]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['date']['date_container']['step'] = [
      '#type' => 'number',
      '#title' => $this->t('Step'),
      '#description' => $this->t('Specifies the legal number intervals.'),
      '#min' => 1,
      '#size' => 4,
      '#states' => [
        'invisible' => [
          ':input[name="properties[datepicker]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Show placeholder for the datepicker only.
    $form['form']['placeholder']['#states'] = [
      'visible' => [
        ':input[name="properties[datepicker]"]' => ['checked' => TRUE],
      ],
    ];

    return $form;
  }

}
