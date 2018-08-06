<?php

namespace Drupal\views_slideshow;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides an interface for a Views slideshow widget.
 */
interface ViewsSlideshowWidgetInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Check if the widget is compatible with the current view configuration.
   *
   * @return bool
   *   TRUE if the widget is compatible with the view.
   */
  public function checkCompatiblity($view);

}
