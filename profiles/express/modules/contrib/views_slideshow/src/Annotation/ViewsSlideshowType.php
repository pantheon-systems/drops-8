<?php

namespace Drupal\views_slideshow\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a slideshow type annotation object.
 *
 * @Annotation
 */
class ViewsSlideshowType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the slideshow type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A list of actions this slideshow type accepts.
   *
   * @var string[]
   */
  public $accepts;

  /**
   * A list of actions this slideshow type implements.
   *
   * @var string[]
   */
  public $calls;

}
