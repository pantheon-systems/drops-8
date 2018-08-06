<?php

namespace Drupal\video_filter;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for ice cream flavor plugins.
 */
interface VideoFilterInterface extends PluginInspectionInterface {

  /**
   * Return the name of the Video Filter codec.
   *
   * @return string
   *    The human-readable name of the codec.
   */
  public function getName();

  /**
   * Return codec sample URL.
   *
   * @return url
   *    The codec sample URL.
   */
  public function getExampleURL();

  /**
   * Return an array of regular expressions for the codec.
   *
   * @return array regexp
   *    Regular expression for the codec.
   */
  public function getRegexp();

  /**
   * Return video player ratio.
   *
   * @return string
   *    Aspect ratio of the video player.
   */
  public function getRatio();

  /**
   * Return video player control bar height.
   *
   * @return int
   *    Video player control bar height.
   */
  public function getControlBarHeight();

  /**
   * Return Video Filter coded usage instructions.
   *
   * @return string
   *    Video Filter coded usage instructions.
   */
  public function instructions();

  /**
   * Return video HTML5 video (iframe).
   *
   * @return url
   *    Video HTML5 video (iframe).
   */
  public function iframe($video);

  /**
   * Return Flash video (flv).
   *
   * @return url
   *    Flash video (flv).
   */
  public function flash($video);

  /**
   * Return HTML code of the video player.
   *
   * @return url
   *    HTML code of the video player.
   */
  public function html($video);

  /**
   * Return embed options (Form API elements).
   *
   * @return array (Drupal Form API)
   *    Embed options (Form API elements).
   */
  public function options();

  /**
   * Returns absolute URL to preview image.
   *
   * @return url
   *    Absolute URL to preview image.
   */
  public function preview($video);

}
