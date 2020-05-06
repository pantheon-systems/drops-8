<?php

namespace Drupal\webform\Element;

/**
 * Defines an interface for webform composite element.
 */
interface WebformCompositeInterface {

  /**
   * Get a renderable array of webform elements.
   *
   * @param array $element
   *   A render array for the current element.
   *
   * @return array
   *   A renderable array of webform elements, containing the base properties
   *   for the composite's webform elements.
   */
  public static function getCompositeElements(array $element);

  /**
   * Initialize a composite's elements.
   *
   * @param array $element
   *   A render array for the current element.
   *
   * @return array
   *   A renderable array of webform elements, containing the base properties
   *   for the composite's webform elements.
   */
  public static function initializeCompositeElements(array &$element);

}
