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
  protected function defineDefaultProperties() {
    return [
      // Time settings.
      'timepicker' => FALSE,
      'time_format' => 'H:i',
      'placeholder' => '',
      'min' => '',
      'max' => '',
      'step' => 60,
    ] + parent::defineDefaultProperties()
      + $this->defineDefaultMultipleProperties();
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    // Set default time format to HTML time.
    if (!isset($element['#time_format'])) {
      $element['#time_format'] = $this->getDefaultProperty('time_format');
    }
    // Set placeholder attribute.
    if (!empty($element['#placeholder'])) {
      $element['#attributes']['placeholder'] = $element['#placeholder'];
    }
    // Prepare element after date format has been updated.
    parent::prepare($element, $webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    if (empty($value)) {
      return '';
    }

    $format = $this->getItemFormat($element);
    if ($format == 'value') {
      $time_format = (isset($element['#time_format'])) ? $element['#time_format'] : 'H:i';
      return static::formatTime($time_format, strtotime($value));
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
        'H:i' => $this->t('24 hour - @format (@time)', ['@format' => 'H:i', '@time' => static::formatTime('H:i')]),
        'H:i:s' => $this->t('24 hour with seconds - @format (@time)', ['@format' => 'H:i:s', '@time' => static::formatTime('H:i:s')]),
        'g:i A' => $this->t('12 hour - @format (@time)', ['@format' => 'g:i A', '@time' => static::formatTime('g:i A')]),
        'g:i:s A' => $this->t('12 hour with seconds - @format (@time)', ['@format' => 'g:i:s A', '@time' => static::formatTime('g:i:s A')]),
      ],
      '#other__option_label' => $this->t('Custom…'),
      '#other__placeholder' => $this->t('Custom time format…'),
      '#other__description' => $this->t('Enter time format using <a href="http://php.net/manual/en/function.date.php">Time Input Format</a>.'),
    ];
    $form['time']['time_container'] = $this->getFormInlineContainer();
    $form['time']['time_container']['min'] = [
      '#type' => 'webform_time',
      '#title' => $this->t('Minimum'),
      '#description' => $this->t('Specifies the minimum time.'),
    ];
    $form['time']['time_container']['max'] = [
      '#type' => 'webform_time',
      '#title' => $this->t('Maximum'),
      '#description' => $this->t('Specifies the maximum time.'),
    ];
    $form['time']['step'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Step'),
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
    ];
    // Show placeholder for the timepicker only.
    $form['form']['placeholder']['#states'] = [
      'visible' => [
        ':input[name="properties[timepicker]"]' => ['checked' => TRUE],
      ],
    ];

    return $form;
  }

  /**
   * Format custom time.
   *
   * @param string $custom_format
   *   A PHP date format string suitable for input to date().
   * @param int $timestamp
   *   (optional) A UNIX timestamp to format.
   *
   * @return string
   *   Formatted time.
   */
  protected static function formatTime($custom_format, $timestamp = NULL) {
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    $date_formatter = \Drupal::service('date.formatter');
    return $date_formatter->format($timestamp ?: time(), 'custom', $custom_format);
  }

}
