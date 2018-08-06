<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformElementBase;

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
    return parent::getDefaultProperties() + [
      'multiple' => FALSE,
      'multiple__header_label' => '',
      // Time settings.
      'time_format' => '',
      'min' => '',
      'max' => '',
      'step' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formatTextItem(array $element, $value, array $options = []) {
    if (empty($value)) {
      return '';
    }

    $format = $this->getItemFormat($element);
    if ($format == 'value') {
      $time_format = (isset($element['#time_format'])) ? $element['#time_format'] : 'H:i';
      return date($time_format, strtotime($value));
    }

    return parent::formatTextItem($element, $value, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Append supported time input format to #default_value description.
    $form['element']['default_value']['#description'] .= '<br />' . $this->t('Accepts any time in any <a href="https://www.gnu.org/software/tar/manual/html_chapter/tar_7.html#Date-input-formats">GNU Date Input Format</a>. Strings such as now, +2 hours, and 4:30 PM are all valid.');

    // Time.
    $form['time'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Time settings'),
    ];
    $form['time']['time_format'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Time format'),
      '#description' => $this->t("Time format is only applicable for browsers that do not have support for the HTML5 time element. Browsers that support the HTML5 time element will display the time using the user's preferred format. Time format is used to format the submitted value."),
      '#options' => [
        'g:i A' => $this->t('12 hour (@time)', ['@time' => date('g:i A')]),
        'g:i:s A' => $this->t('12 hour with seconds (@time)', ['@time' => date('g:i:s A')]),
        'H:i' => $this->t('24 hour (@time)', ['@time' => date('H:i')]),
        'H:i:s' => $this->t('24 hour with seconds (@time)', ['@time' => date('H:i:s')]),
      ],
      '#other__option_label' => $this->t('Custom...'),
      '#other__placeholder' => $this->t('Custom time format...'),
      '#other__description' => $this->t('Enter time format using <a href="http://php.net/manual/en/function.date.php">Time Input Format</a>.'),
    ];
    $form['time']['min'] = [
      '#type' => 'webform_time',
      '#title' => $this->t('Min'),
      '#description' => $this->t('Specifies the minimum time.'),
    ];
    $form['time']['max'] = [
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
