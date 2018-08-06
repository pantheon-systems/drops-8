<?php

namespace Drupal\entity_browser;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for widget selector plugins.
 */
abstract class WidgetSelectorBase extends PluginBase implements WidgetSelectorInterface {

  use PluginConfigurationFormTrait;

  /**
   * Plugin label.
   *
   * @var string
   */
  protected $label;

  /**
   * Available widgets.
   *
   * @var array
   */
  protected $widgets_ids;

  /**
   * ID of the default widget.
   *
   * @var string
   */
  protected $defaultWidget;

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
    $this->widget_ids = isset($this->configuration['widget_ids']) ? $this->configuration['widget_ids'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return array_diff_key(
      $this->configuration,
      ['widget_ids' => 0]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep(
      $this->defaultConfiguration(),
      $configuration
    );
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
  public function label() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultWidget() {
    return $this->defaultWidget;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultWidget($widget) {
    $this->defaultWidget = $widget;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submit(array &$form, FormStateInterface $form_state) {}

}
