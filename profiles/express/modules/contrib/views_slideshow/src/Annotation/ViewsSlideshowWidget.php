<?php

namespace Drupal\views_slideshow\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a widget annotation object.
 *
 * @Annotation
 */
class ViewsSlideshowWidget extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the widget.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The widget type used by the widget.
   *
   * @var string
   */
  public $type;

}
