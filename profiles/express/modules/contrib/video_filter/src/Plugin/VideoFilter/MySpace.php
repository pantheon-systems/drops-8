<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides MySpace codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "myspace",
 *   name = @Translation("MySpace"),
 *   example_url = "http://myspace.com/video/vid/1234567890",
 *   regexp = {
 *     "/vids\.myspace\.com\/.*VideoID=([0-9]+)/i",
 *     "/myspace\.com\/video\/([a-z])+\/([0-9]+)/i",
 *     "/myspace\.com\/video\/([a-z0-9\-_]+)\/([a-z0-9\-_]+)\/([a-z0-9]+)/i",
 *     "/myspace\.com\/([a-z0-9\-_]+)\/videos\/([a-z0-9\-_]+)\/([a-z0-9]+)/i",
 *   },
 *   ratio = "620/400",
 *   control_bar_height = 40,
 * )
 */
class MySpace extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function flash($video) {
    // The last match is the ID we need.
    $last = count($video['codec']['matches']);
    return [
      'src' => '//mediaservices.myspace.com/services/media/embed.aspx/m=' . $video['codec']['matches'][$last - 1],
    ];
  }

}
