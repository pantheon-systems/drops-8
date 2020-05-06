<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'scale' element.
 *
 * @WebformElement(
 *   id = "webform_scale",
 *   label = @Translation("Scale"),
 *   description = @Translation("Provides a form element for input of a numeric scale."),
 *   category = @Translation("Advanced elements"),
 * )
 */
class WebformScale extends NumericBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = [
      'min' => 1,
      'max' => 5,
      'min_text' => '',
      'max_text' => '',
      'scale_size' => 'medium',
      'scale_type' => 'circle',
      'scale_text' => 'below',
      // Wrapper.
      'wrapper_type' => 'fieldset',
    ] + parent::defineDefaultProperties();
    unset(
      $properties['size'],
      $properties['minlength'],
      $properties['maxlength'],
      $properties['placeholder'],
      $properties['autocomplete'],
      $properties['format_items'],
      $properties['format_items_html'],
      $properties['format_items_text']
    );
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return parent::preview() + [
      '#min' => 1,
      '#max' => 5,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['number']['#title'] = $this->t('Scale settings');

    $form['number']['number_container']['min']['#min'] = 0;
    $form['number']['number_container']['min']['#step'] = 1;
    $form['number']['number_container']['min']['#required'] = TRUE;

    $form['number']['number_container']['max']['#min'] = 0;
    $form['number']['number_container']['max']['#step'] = 1;
    $form['number']['number_container']['max']['#required'] = TRUE;

    $form['number']['min_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Minimum label'),
      '#description' => $this->t('Label for the minimum value in the scale.'),
    ];
    $form['number']['max_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum label'),
      '#description' => $this->t('Label for the maximum value in the scale.'),
    ];

    $form['number']['scale_container'] = $this->getFormInlineContainer();
    $form['number']['scale_container']['scale_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Scale size'),
      '#options' => [
        'small' => $this->t('Small (@size)', ['@size' => '24px']),
        'medium' => $this->t('Medium (@size)', ['@size' => '36px']),
        'large' => $this->t('Large (@size)', ['@size' => '48px']),
      ],
      '#required' => TRUE,
    ];
    $form['number']['scale_container']['scale_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Scale type'),
      '#options' => [
        'circle' => $this->t('Circle'),
        'square' => $this->t('Square'),
        'flexbox' => $this->t('Flexbox'),
      ],
      '#required' => TRUE,
    ];
    $form['number']['scale_container']['scale_text'] = [
      '#type' => 'select',
      '#title' => $this->t('Scale text'),
      '#options' => [
        'above' => $this->t('Above'),
        'below' => $this->t('Below'),
      ],
      '#required' => TRUE,
    ];
    return $form;
  }

}
