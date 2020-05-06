<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformRating as WebformRatingElement;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'rating' element.
 *
 * @WebformElement(
 *   id = "webform_rating",
 *   label = @Translation("Rating"),
 *   description = @Translation("Provides a form element to rate something using an attractive voting widget."),
 *   category = @Translation("Advanced elements"),
 * )
 */
class WebformRating extends Range {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = [
      // Number settings.
      'max' => 5,
      // General settings.
      'default_value' => 0,
      // Rating settings.
      'star_size' => 'medium',
      'reset' => FALSE,
    ] + parent::defineDefaultProperties();
    unset(
      $properties['output'],
      $properties['output__field_prefix'],
      $properties['output__field_suffix'],
      $properties['output__attributes']
    );
    return $properties;
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    $element += ['#min' => 0, '#max' => 5];
    return parent::getTestValues($element, $webform, $options);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    $format = $this->getItemFormat($element);

    switch ($format) {
      case 'star':
        // Always return the raw value when the rating widget is included in an
        // email or PDF.
        if (!empty($options['email']) || !empty($options['pdf'])) {
          return parent::formatTextItem($element, $webform_submission, $options);
        }

        $build = [
          '#value' => $value,
          '#readonly' => TRUE,
        ] + $element;
        return WebformRatingElement::buildRateIt($build);

      default:
        return parent::formatHtmlItem($element, $webform_submission, $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return 'star';
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    return parent::getItemFormats() + [
      'star' => $this->t('Star'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [
      '#type' => $this->getTypeName(),
      '#title' => $this->getPluginLabel(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['number']['#title'] = $this->t('Rating settings');
    $form['number']['star_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Star size'),
      '#options' => [
        'small' => $this->t('Small (@size)', ['@size' => '16px']),
        'medium' => $this->t('Medium (@size)', ['@size' => '24px']),
        'large' => $this->t('Large (@size)', ['@size' => '32px']),
      ],
      '#required' => TRUE,
    ];
    $form['number']['reset'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show reset button'),
      '#description' => $this->t('If checked, a reset button will be placed before the rating element.'),
      '#return_value' => TRUE,
    ];

    // Only allow a rating element to be required if the min value can be
    // set to 0.
    $form['validation']['required_container']['#states'] = [
      'visible' => [
        ':input[name="properties[min]"]' => ['value' => '0'],
      ],
    ];

    return $form;
  }

}
