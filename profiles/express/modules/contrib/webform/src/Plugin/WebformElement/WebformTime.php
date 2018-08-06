<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'webform_time' element.
 *
 * @WebformElement(
 *   id = "webform_time",
 *   api = "http://www.w3schools.com/tags/tag_time.asp",
 *   label = @Translation("Time"),
 *   description = @Translation("Provides a form element for time selection."),
 *   category = @Translation("Date/time elements"),
 * )
 */
class WebformTime extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      // Time settings.
      'timepicker' => FALSE,
      'time_format' => 'H:i',
      'min' => '',
      'max' => '',
      'step' => '',
    ] + parent::getDefaultProperties() + $this->getDefaultMultipleProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    // Set default time format to HTML time.
    if (!isset($element['#time_format'])) {
      $element['#time_format'] = $this->getDefaultProperty('time_format');
    }

    // Prepare element after date format has been updated.
    parent::prepare($element, $webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  public function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    if (empty($value)) {
      return '';
    }

    $format = $this->getItemFormat($element);
    if ($format == 'value') {
      $time_format = (isset($element['#time_format'])) ? $element['#time_format'] : 'H:i';
      return date($time_format, strtotime($value));
    }

    return parent::formatTextItem($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Append supported time input format to #default_value description.
    $form['default']['default_value']['#description'] .= '<br />' . $this->t('Accepts any time in any <a href="https://www.gnu.org/software/tar/manual/html_chapter/tar_7.html#Date-input-formats">GNU Date Input Format</a>. Strings such as now, +2 hours, and 4:30 PM are all valid.');

    // Time.
    $form['time'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Time settings'),
    ];

    $form['time']['timepicker'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use time picker'),
      '#description' => $this->t('If checked, HTML5 time element will be replaced with <a href="http://jonthornton.github.io/jquery-timepicker/">jQuery UI timepicker</a>'),
      '#return_value' => TRUE,
    ];
    $form['time']['time_format'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Time format'),
      '#options' => [
        'H:i' => $this->t('24 hour - @format (@time)', ['@format' => 'H:i', '@time' => date('H:i')]),
        'H:i:s' => $this->t('24 hour with seconds - @format (@time)', ['@format' => 'H:i:s', '@time' => date('H:i:s')]),
        'g:i A' => $this->t('12 hour - @format (@time)', ['@format' => 'g:i A', '@time' => date('g:i A')]),
        'g:i:s A' => $this->t('12 hour with seconds - @format (@time)', ['@format' => 'g:i:s A', '@time' => date('g:i:s A')]),
      ],
      '#other__option_label' => $this->t('Custom...'),
      '#other__placeholder' => $this->t('Custom time format...'),
      '#other__description' => $this->t('Enter time format using <a href="http://php.net/manual/en/function.date.php">Time Input Format</a>.'),
    ];
    $form['time']['time_container'] = $this->getFormInlineContainer();
    $form['time']['time_container']['min'] = [
      '#type' => 'webform_time',
      '#title' => $this->t('Min'),
      '#description' => $this->t('Specifies the minimum time.'),
    ];
    $form['time']['time_container']['max'] = [
      '#type' => 'webform_time',
      '#title' => $this->t('Max'),
      '#description' => $this->t('Specifies the maximum time.'),
    ];
    $form['time']['step'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Step'),
      '#description' => $this->t('Specifies the minute intervals.'),
      '#options' => [
        '' => $this->t('1 minute'),
        30 => $this->t('5 minutes'),
        600 => $this->t('10 minutes'),
        900 => $this->t('15 minutes'),
        1200 => $this->t('20 minutes'),
        1800 => $this->t('30 minutes'),
      ],
      '#other__type' => 'number',
      '#other__description' => $this->t('Enter interval in seconds.'),
    ];
    return $form;
  }

}
