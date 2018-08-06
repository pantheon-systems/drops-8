<?php

/**
 * @file
 * Contains \Drupal\linkit\ConfigurableAttributeInterface.
 */

namespace Drupal\linkit;

use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the interface for configurable attributes.
 *
 * @see \Drupal\linkit\Annotation\Attribute
 * @see \Drupal\linkit\ConfigurableAttributeBase
 * @see \Drupal\linkit\AttributeInterface
 * @see \Drupal\linkit\AttributeBase
 * @see \Drupal\linkit\AttributeManager
 * @see plugin_api
 */
interface ConfigurableAttributeInterface extends AttributeInterface, PluginFormInterface {
}
