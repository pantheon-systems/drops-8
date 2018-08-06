<?php

namespace Drupal\views_slideshow\Plugin\ViewsSlideshowWidgetType;

use Drupal\views_slideshow\ViewsSlideshowWidgetTypeBase;

/**
 * Provides a counter widget type.
 *
 * @ViewsSlideshowWidgetType(
 *   id = "views_slideshow_slide_counter",
 *   label = @Translation("Slide counter"),
 *   accepts = {
 *     "transitionBegin" = {"required" = TRUE},
 *     "goToSlide",
 *     "previousSlide",
 *     "nextSlide"
 *   },
 *   calls = {}
 * )
 */
class Counter extends ViewsSlideshowWidgetTypeBase {
}
