<?php

namespace Drupal\media_entity_test_type\Plugin\MediaEntity\Type;

use Drupal\Core\Form\FormStateInterface;
use Drupal\media_entity\Plugin\MediaEntity\Type\Generic;

/**
 * Provides generic media type.
 *
 * @MediaType(
 *   id = "test_type",
 *   label = @Translation("Test type"),
 *   description = @Translation("Test media type.")
 * )
 */
class TestType extends Generic {

  /**
   * {@inheritdoc}
   */
  public function providedFields() {
    return [
      'field_1' => $this->t('Field 1'),
      'field_2' => $this->t('Field 2'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'test_config_value' => 'This is default value.',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['test_config_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Test config value'),
      '#default_value' => empty($this->configuration['test_config_value']) ? NULL : $this->configuration['test_config_value'],
    ];

    return $form;
  }

}
