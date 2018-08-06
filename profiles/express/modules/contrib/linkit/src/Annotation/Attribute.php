<?php

/**
 * @file
 * Contains \Drupal\linkit\Annotation\Attribute.
 */

namespace Drupal\linkit\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an attribute annotation object.
 *
 * Plugin Namespace: Plugin\Linkit\Attribute
 *
 * For a working example, see \Drupal\linkit\Plugin\Linkit\Attribute\Title
 *
 * @see \Drupal\linkit\AttributeInterface
 * @see \Drupal\linkit\AttributeBase
 * @see \Drupal\linkit\AttributeManager
 * @see plugin_api
 *
 * @Annotation
 */
class Attribute extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the attribute.
   *
   * The string should be wrapped in a @Translation().
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The real HTML attribute name for this attribute.
   *
   * @var string
   */
  public $html_name;

  /**
   * A brief description of the attribute.
   *
   * This will be shown when adding or configuring a profile.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $description = '';

  /**
   * A default weight for the attribute.
   *
   * @var int (optional)
   */
  public $weight = 0;

}
