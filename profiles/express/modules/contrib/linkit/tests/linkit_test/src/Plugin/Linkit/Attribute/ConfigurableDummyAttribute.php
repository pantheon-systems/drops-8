<?php

/**
 * @file
 * Contains \Drupal\linkit_test\Plugin\Linkit\Attribute\ConfigurableDummyAttribute.
 */

namespace Drupal\linkit_test\Plugin\Linkit\Attribute;

use Drupal\Core\Form\FormStateInterface;
use Drupal\linkit\ConfigurableAttributeBase;

/**
 * Accesskey attribute.
 *
 * @Attribute(
 *   id = "configurable_dummy_attribute",
 *   label = @Translation("Configurable Dummy Attribute"),
 *   html_name = "configurabledummyattribute",
 *   description = @Translation("Configurable Dummy Attribute")
 * )
 */
class ConfigurableDummyAttribute extends ConfigurableAttributeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFormElement($default_value) {
    return [
      '#type' => 'textfield',
      '#title' => t('DummyAttribute'),
      '#default_value' => $default_value,
      '#maxlength' => 255,
      '#size' => 40,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'dummy_setting' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['dummy_setting'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Dummy setting'),
      '#default_value' => $this->configuration['dummy_setting'],
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
    $this->configuration['dummy_setting'] = $form_state->getValue('dummy_setting');
  }

}
