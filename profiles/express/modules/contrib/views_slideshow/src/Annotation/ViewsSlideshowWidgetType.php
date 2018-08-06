<?php

namespace Drupal\views_slideshow\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a widget type annotation object.
 *
 * @Annotation
 */
class ViewsSlideshowWidgetType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the widget type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A list of actions this widget type accepts.
   *
   * @var array
   */
  public $accepts;

  /**
   * A list of actions this widget type implements.
   *
   * @var array
   */
  public $calls;

}
