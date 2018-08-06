<?php

namespace Drupal\views_slideshow;

/**
 * Provides an interface for Views slideshow skins.
 */
interface ViewsSlideshowSkinInterface {

  /**
   * Returns a array of libraries to attach when the skin is used.
   *
   * @return array
   *   The libraries to be attached.
   */
  public function getLibraries();

  /**
   * Returns a class to be added to templates.
   *
   * @return string
   *   The class name.
   */
  public function getClass();

}
