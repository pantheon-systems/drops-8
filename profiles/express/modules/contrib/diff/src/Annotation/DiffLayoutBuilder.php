<?php

namespace Drupal\diff\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a DiffLayoutBuilder annotation object.
 *
 * Diff builders handle how fields are compared by the diff module.
 *
 * @Annotation
 */
class DiffLayoutBuilder extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the diff layout builder.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The plugin description.
   *
   * @var string
   */
  public $description;

}
