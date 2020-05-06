<?php

namespace Drupal\webform\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a webform source entity annotation object.
 *
 * Plugin Namespace: Plugin\WebformSourceEntity.
 *
 * For a working example, see
 * \Drupal\webform\Plugin\WebformSourceEntity\QueryStringWebformSourceEntity
 *
 * @see hook_webform_source_entity_info()
 * @see \Drupal\webform\Plugin\WebformSourceEntityInterface
 * @see \Drupal\webform\Plugin\WebformSourceEntityManager
 * @see \Drupal\webform\Plugin\WebformSourceEntityManagerInterface
 * @see plugin_api
 *
 * @Annotation
 */
class WebformSourceEntity extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A brief description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

  /**
   * Weight (priority) of the plugin.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * The element's module dependencies.
   *
   * @var array
   *
   * @see webform_webform_element_info_alter()
   */
  public $dependencies = [];

}
