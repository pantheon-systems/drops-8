<?php

/**
 * @file
 * Contains \Drupal\linkit\Plugin\Linkit\Attribute\Target.
 */

namespace Drupal\linkit\Plugin\Linkit\Attribute;

use Drupal\Core\Form\FormStateInterface;
use Drupal\linkit\ConfigurableAttributeBase;

/**
 * Target attribute.
 *
 * @Attribute(
 *   id = "target",
 *   label = @Translation("Target"),
 *   html_name = "target"
 * )
 */
class Target extends ConfigurableAttributeBase {

  const SELECT_LIST = 'select_list';
  const SIMPLE_CHECKBOX = 'simple_checkbox';

  /**
   * {@inheritdoc}
   */
  public function buildFormElement($default_value) {
    switch ($this->configuration['widget_type']) {
      case self::SELECT_LIST:
        return [
          '#type' => 'select',
          '#title' => t('Target'),
          '#options' => [
            '' => '',
            '_blank' => t('New window (_blank)'),
            '_top' => t('Top window (_top)'),
            '_self' => t('Same window (_self)'),
            '_parent' => t('Parent window (_parent)')
          ],
          '#default_value' => $default_value,
        ];
      case self::SIMPLE_CHECKBOX:
        return [
          '#type' => 'checkbox',
          '#title' => t('Open in new window'),
          '#default_value' => $default_value,
          '#return_value' => '_blank',
        ];
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'widget_type' => self::SIMPLE_CHECKBOX,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['widget_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Widget type'),
      '#default_value' => $this->configuration['widget_type'],
      '#options' =>  [
        self::SELECT_LIST => $this->t('Selectlist with predefined targets.'),
        self::SIMPLE_CHECKBOX => $this->t('Simple checkbox to allow links to be opened in a new browser window or tab.'),
      ],
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
    $this->configuration['widget_type'] = $form_state->getValue('widget_type');
  }

}
