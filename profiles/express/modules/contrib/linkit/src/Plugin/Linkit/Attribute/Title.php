<?php

/**
 * @file
 * Contains \Drupal\linkit\Plugin\Linkit\Attribute\Title.
 */

namespace Drupal\linkit\Plugin\Linkit\Attribute;

use Drupal\Core\Form\FormStateInterface;
use Drupal\linkit\ConfigurableAttributeBase;

/**
 * Title attribute.
 *
 * @Attribute(
 *   id = "title",
 *   label = @Translation("Title"),
 *   html_name = "title",
 *   description = @Translation("Basic input field for the title attribute.")
 * )
 */
class Title extends ConfigurableAttributeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFormElement($default_value) {
    $element = [
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#default_value' => $default_value,
      '#maxlength' => 255,
      '#size' => 40,
      '#placeholder' => t('The "title" attribute value'),
    ];

    if ($this->configuration['automatic_title']) {
      $element['#attached']['library'][] = 'linkit/linkit.attribute.title';
      $element['#placeholder'] = t('The "title" attribute value (auto populated)');
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'automatic_title' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['automatic_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically populate title'),
      '#default_value' => $this->configuration['automatic_title'],
      '#description' => $this->t('Automatically populate the title attribute with the title from the match selection.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['automatic_title'] = $form_state->getValue('automatic_title');
  }

}
