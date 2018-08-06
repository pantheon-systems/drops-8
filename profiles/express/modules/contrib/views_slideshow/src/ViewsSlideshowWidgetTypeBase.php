<?php

namespace Drupal\views_slideshow;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for a Views slideshow widget type.
 */
abstract class ViewsSlideshowWidgetTypeBase extends PluginBase implements ViewsSlideshowWidgetTypeInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Add field to see if they would like to hide controls if there is only
    // one slide.
    $form['hide_on_single_slide'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide controls if there is only one slide'),
      '#default_value' => $this->getConfiguration()['hide_on_single_slide'],
      '#description' => $this->t('Should the controls be hidden if there is only one slide.'),
      '#states' => [
        'visible' => [
          ':input[name="' . $this->getConfiguration()['dependency'] . '[enable]"]' => ['checked' => TRUE],
        ],
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
  public function defaultConfiguration() {
    return [
      'enable' => ['default' => 0],
      'weight' => ['default' => 1],
      'hide_on_single_slide' => ['default' => 0],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'id' => $this->getPluginId(),
    ] + $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function checkCompatiblity($slideshow) {
    $is_compatible = 1;
    // Check if every required accept value in the widget has a
    // corresponding calls value in the slideshow.
    foreach ($this->pluginDefinition['accepts'] as $accept_key => $accept_value) {
      if (is_array($accept_value) && !empty($accept_value['required']) && !in_array($accept_key, $slideshow['calls'])) {
        $is_compatible = 0;
        break;
      }
    }

    // No need to go through this if it's not compatible.
    if ($is_compatible) {
      // Check if every required calls value in the widget has a
      // corresponding accepts call.
      foreach ($this->pluginDefinition['calls'] as $calls_key => $calls_value) {
        if (is_array($calls_value) && !empty($calls_value['required']) && !in_array($calls_key, $slideshow['accepts'])) {
          $is_compatible = 0;
          break;
        }
      }
    }

    return $is_compatible;
  }

}
