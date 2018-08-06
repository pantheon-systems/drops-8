<?php

namespace Drupal\entity_browser;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides base form methods for configurable plugins entity browser.
 */
trait PluginConfigurationFormTrait {

  /**
   * Implements PluginFormInterface::buildConfigurationForm().
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Implements PluginFormInterface::validateConfigurationForm().
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Implements PluginFormInterface::submitConfigurationForm().
   *
   * This is the default implementation for the most common cases where the form
   * element names match keys in configuration array. Plugins can override this
   * if they need more complex logic.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if ($this instanceof WidgetInterface) {
      $values = $values['table'][$this->uuid()]['form'];
    }

    if (!empty($values)) {
      foreach ($values as $key => $value) {
        if (array_key_exists($key, $this->configuration)) {
          $this->configuration[$key] = $value;
        }
      }
    }
  }

}
