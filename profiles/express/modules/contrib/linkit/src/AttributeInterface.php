<?php

/**
 * @file
 * Contains \Drupal\linkit\AttributeInterface.
 */

namespace Drupal\linkit;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines the interface for attributes plugins.
 *
 * @see \Drupal\linkit\Annotation\Attribute
 * @see \Drupal\linkit\AttributeBase
 * @see \Drupal\linkit\AttributeManager
 * @see plugin_api
 */
interface AttributeInterface extends PluginInspectionInterface, ConfigurablePluginInterface {

  /**
   * Returns the attribute label.
   *
   * @return string
   *   The attribute label.
   */
  public function getLabel();

  /**
   * Returns the attribute description.
   *
   * @return string
   *   The attribute description.
   */
  public function getDescription();

  /**
   * Returns the attribute html name. This is the name of the attribute
   * that will be inserted in the <code>&lt;a&gt;</code> tag.
   *
   * @return string
   *   The attribute html name.
   */
  public function getHtmlName();

  /**
   * Returns the weight of the attribute.
   *
   * @return int|string
   *   Either the integer weight of the attribute or an empty string.
   */
  public function getWeight();

  /**
   * Sets the weight for this attribute.
   *
   * @param int $weight
   *   The weight for this attribute.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * The form element structure for this attribute to be used in the dialog.
   *
   * @param mixed $default_value
   *   The default value for the element. Used when editing an attribute in the
   *   dialog.
   *
   * @return array
   *   The form element.
   */
  public function buildFormElement($default_value);

}
