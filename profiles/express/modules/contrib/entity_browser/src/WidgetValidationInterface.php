<?php

namespace Drupal\entity_browser;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;


/**
 * Defines the interface for entity browser widget validations.
 */
interface WidgetValidationInterface extends PluginInspectionInterface, ConfigurablePluginInterface {

  /**
   * Returns the widget validation label.
   *
   * @return string
   *   The widget validation label.
   */
  public function label();

  /**
   * Validates the widget.
   *
   * @param array $entities
   *   Array of selected entities.
   * @param array $options
   *   (Optional) Array of options needed by the constraint validator.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationListInterface
   *   A list of constraint violations. If the list is empty, validation
   *   succeeded.
   */
  public function validate(array $entities, $options = []);
}
