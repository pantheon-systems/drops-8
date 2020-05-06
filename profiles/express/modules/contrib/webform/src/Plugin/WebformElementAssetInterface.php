<?php

namespace Drupal\webform\Plugin;

/**
 * Defines the interface for webform elements with CSS and JavaScript assets.
 */
interface WebformElementAssetInterface {

  /**
   * Determine if the element has assets.
   *
   * @return bool
   *   TRUE if the element has assets.
   */
  public function hasAssets();

  /**
   * Returns the webform element's asset ID.
   *
   * This is used to prevent duplicate CSS and JavaScript from being appended
   * to a webform.
   *
   * @return string
   *   The webform element's asset ID.
   */
  public function getAssetId();

  /**
   * Returns the webform element's CSS.
   *
   * @return string
   *   The webform element's CSS.
   */
  public function getCss();

  /**
   * Returns the webform element's JavaScript.
   *
   * @return string
   *   The webform element's CSS.
   */
  public function getJavaScript();

}
