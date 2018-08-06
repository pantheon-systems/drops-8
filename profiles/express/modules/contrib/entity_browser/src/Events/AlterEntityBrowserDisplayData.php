<?php

namespace Drupal\entity_browser\Events;

use Drupal\Core\Form\FormStateInterface;

/**
 * Allows data for an entity browser element to be altered.
 */
class AlterEntityBrowserDisplayData extends EventBase {

  /**
   * Data to process.
   *
   * @var array
   */
  protected $data;

  /**
   * Form state object.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState;

  /**
   * Plugin definition.
   *
   * @var array
   */
  protected $pluginDefinition;

  /**
   * Constructs a EntitySelectionEvent object.
   *
   * @param string $entity_browser_id
   *   Entity browser ID.
   * @param string $instance_uuid
   *   Entity browser instance UUID.
   * @param array $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   * @param array $data
   *   Data to process.
   */
  public function __construct($entity_browser_id, $instance_uuid, array $plugin_definition, FormStateInterface $form_state, array $data) {
    parent::__construct($entity_browser_id, $instance_uuid);
    $this->data = $data;
    $this->formState = $form_state;
    $this->pluginDefinition = $plugin_definition;
  }

  /**
   * Gets form state.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *   Form state object.
   */
  public function getFormState() {
    return $this->formState;
  }

  /**
   * Gets data array.
   *
   * @return array
   *   Data array.
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Gets plugin definition array.
   *
   * @return array
   *   Plugin definition array.
   */
  public function getPluginDefinition() {
    return $this->pluginDefinition;
  }

  /**
   * Sets data array.
   *
   * @param array $data
   *   Data array.
   */
  public function setData($data) {
    $this->data = $data;
  }

}
