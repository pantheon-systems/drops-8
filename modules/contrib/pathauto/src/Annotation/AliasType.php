<?php

namespace Drupal\pathauto\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an AliasType annotation.
 *
 * @Annotation
 */
class AliasType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the action plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The token types.
   *
   * @var string[]
   */
  public $types = [];

}
