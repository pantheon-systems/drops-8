<?php

namespace Drupal\video_filter\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Video Filter codec annotation object.
 *
 * Plugin Namespace: Plugin\video_filter\VideoFilter.
 *
 * @see \Drupal\video_filter\Plugin\VideoFilterManager
 * @see plugin_api
 *
 * @Annotation
 */
class VideoFilter extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the codec.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

  /**
   * The example URL.
   *
   * @var url
   */
  public $example_url;

  /**
   * Regular expressions to match the video.
   *
   * @var array of regexp
   */
  public $regexp = [];

  /**
   * The video player ratio.
   *
   * @var float
   */
  public $ratio;

  /**
   * The video player control bar height.
   *
   * @var int
   */
  public $control_bar_height;

}
