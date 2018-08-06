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

    return parent::getDefaultProperties() + [
      // Date settings.
      'date_date_format' => $date_format,
      'step' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission) {
    parent::prepare($element, $webform_submission);
    // Set the (input) type attribute to 'date' since #min and #max will
    // override the default attributes.
    // @see \Drupal\Core\Render\Element\Date::getInfo
    $element['#attributes']['type'] = 'date';

    // Issue #2817693 by danbohea: Min date option not working with jQuery UI
    // datepicker.
    $element['#attached']['library'][] = 'webform/webform.element.date';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

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
    ];
    $form['date']['step'] = [
      '#type' => 'number',
      '#title' => $this->t('Step'),
      '#description' => $this->t('Specifies the legal number intervals.'),
      '#min' => 1,
      '#size' => 4,
      '#weight' => 10,
    ];
    return $form;
  }

}
