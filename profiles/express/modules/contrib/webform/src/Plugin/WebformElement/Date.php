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
  public function getDefaultProperties() {
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
      'date_date_format' => $date_format,
      'step' => '',
      'size' => '',
    ] + parent::getDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    // Unset unsupported date format for date elements that are not using a
    // datepicker.
    if (empty($element['#datepicker'])) {
      unset($element['#date_date_format']);
    }

    // Set defautl date format to HTML date.
    if (!isset($element['#date_date_format'])) {
      $element['#date_date_format'] = $this->getDefaultProperty('date_date_format');
    }

    // Prepare element after date format has been updated.
    parent::prepare($element, $webform_submission);

    // Set the (input) type attribute to 'date' since #min and #max will
    // override the default attributes.
    // @see \Drupal\Core\Render\Element\Date::getInfo
    $element['#attributes']['type'] = 'date';

    // Issue #2817693: Min date option not working with jQuery UI
    // datepicker.
    $element['#attached']['library'][] = 'webform/webform.element.date';

    // Convert date element into textfield with date picker.
    if (!empty($element['#datepicker'])) {
      $element['#attributes']['type'] = 'text';

      // Must manually set 'data-drupal-date-format' to trigger date picker.
      // @see \Drupal\Core\Render\Element\Date::processDate
      $element['#attributes']['data-drupal-date-format'] = [$element['#date_date_format']];

      // Format default value.
      if (isset($element['#default_value'])) {
        $element['#default_value'] = date($element['#date_date_format'], strtotime($element['#default_value']));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['date']['datepicker'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use date picker'),
      '#description' => $this->t('If checked, the HTML5 date element will be replaced with <a href="https://jqueryui.com/datepicker/">jQuery UI datepicker</a>'),
      '#return_value' => TRUE,
    ];
    $date_format = DateFormat::load('html_date')->getPattern();
    $form['date']['date_date_format'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Date format'),
      '#options' => [
        $date_format => $this->t('HTML date - @format (@date)', ['@format' => $date_format, '@date' => date($date_format)]),
        'l, F j, Y' => $this->t('Long date - @format (@date)', ['@format' => 'l, F j, Y', '@date' => date('l, F j, Y')]),
        'D, m/d/Y' => $this->t('Medium date - @format (@date)', ['@format' => 'D, m/d/Y', '@date' => date('D, m/d/Y')]),
        'm/d/Y' => $this->t('Short date - @format (@date)', ['@format' => 'm/d/Y', '@date' => date('m/d/Y')]),
      ],
      '#description' => $this->t("Date format is only applicable for browsers that do not have support for the HTML5 date element. Browsers that support the HTML5 date element will display the date using the user's preferred format."),
      '#other__option_label' => $this->t('Custom...'),
      '#other__placeholder' => $this->t('Custom date format...'),
      '#other__description' => $this->t('Enter date format using <a href="http://php.net/manual/en/function.date.php">Date Input Format</a>.'),
      '#states' => [
        'visible' => [
          ':input[name="properties[datepicker]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['date']['step'] = [
      '#type' => 'number',
      '#title' => $this->t('Step'),
      '#description' => $this->t('Specifies the legal number intervals.'),
      '#min' => 1,
      '#size' => 4,
      '#weight' => 10,
      '#states' => [
        'invisible' => [
          ':input[name="properties[datepicker]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    return $form;
  }

}
