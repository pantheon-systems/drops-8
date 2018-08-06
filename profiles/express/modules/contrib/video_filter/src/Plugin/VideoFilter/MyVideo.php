<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides MyVideo codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "myvideo",
 *   name = @Translation("MyVideo"),
 *   example_url = "http://www.myvideo.de/filme/story-title-[video-id]",
 *   regexp = {
 *     "/myvideo\.de\/(.+)\-([0-9]+)/i",
 *   },
 *   ratio = "16/9",
 * )
 */
class MyVideo extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function iframe($video) {
    return [
      'src' => 'http://www.myvideo.de/embedded/public/' . $video['codec']['matches'][2],
      'properties' => [
        'allowfullscreen' => 'true',
      ],
    ];
  }

}
