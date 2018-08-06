<?php

namespace Drupal\diff\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a FieldDiffBuilder annotation object.
 *
 * Diff builders handle how fields are compared by the diff module.
 *
 * Additional annotation keys for diff builders can be defined in
 * hook_field_diff_builder_info_alter().
 *
 * @Annotation
 *
 * @see \Drupal\diff\FieldDiffBuilderPluginManager
 * @see \Drupal\diff\FieldDiffBuilderInterface
 */
class FieldDiffBuilder extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the diff builder.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * An array of field types the diff builder supports.
   *
   * @var array
   */
  public $field_types = [];

  /**
   * The weight of the plugin that defines its importance when applied.
   *
   * @var int
   */
  public $weight = 0;
}
