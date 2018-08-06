<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides GodTube codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "godtube",
 *   name = @Translation("GodTube"),
 *   example_url = "http://www.godtube.com/watch/?v=123abc",
 *   regexp = {
 *     "/godtube\.com\/watch\/\?v=([a-z0-9\-_]+)/i",
 *   },
 *   ratio = "400/283",
 *   control_bar_height = 40,
 * )
 */
class GodTube extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function iframe($video) {
    return [
      'src' => '//www.godtube.com/embed/watch/' . $video['codec']['matches'][1],
    ];
  }

}
