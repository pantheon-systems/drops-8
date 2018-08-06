<?php

namespace Drupal\views_slideshow;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides an interface for Views slideshow types.
 */
interface ViewsSlideshowTypeInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {}
