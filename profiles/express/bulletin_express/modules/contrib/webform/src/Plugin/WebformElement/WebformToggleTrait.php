<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'toggle' trait.
 */
trait WebformToggleTrait {

  /**
   * {@inheritdoc}
   */
  public function getTranslatableProperties() {
    return array_merge(parent::getTranslatableProperties(), ['on_text', 'off_text']);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['toggle'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('toggle settings'),
    ];
    $form['toggle']['toggle_theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Toggle theme'),
      '#options' => [
        'light' => $this->t('Light'),
        'dark' => $this->t('Dark'),
        'iphone' => $this->t('iPhone'),
        'modern' => $this->t('Modern'),
        'soft' => $this->t('Soft'),
      ],
      '#required' => TRUE,
    ];
    $form['toggle']['toggle_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Toggle size'),
      '#options' => [
        'small' => $this->t('Small (@size)', ['@size' => '16px']),
        'medium' => $this->t('Medium (@size)', ['@size' => '24px']),
        'large' => $this->t('Large (@size)', ['@size' => '32px']),
      ],
      '#required' => TRUE,
    ];
    $form['toggle']['on_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Toggle on text'),
    ];
    $form['toggle']['off_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Toggle off text'),
    ];
    return $form;
  }

}
