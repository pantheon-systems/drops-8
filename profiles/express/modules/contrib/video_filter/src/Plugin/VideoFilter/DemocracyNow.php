<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides DemocracyNow codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "democracynow",
 *   name = @Translation("Democracy Now"),
 *   example_url = "http://www.democracynow.org/shows/2015/3/20",
 *   regexp = {
 *     "/democracynow\.org\/shows\/([0-9]+)\/([0-9]+)\/([0-9]+)/",
 *     "/democracynow\.org\/([0-9]+)\/([0-9]+)\/([0-9]+)\/([a-zA-Z0-9\-_]+)/",
 *   },
 *   ratio = "16/9",
 * )
 */
class DemocracyNow extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function iframe($video) {
    if (!empty($video['codec']['matches'][4])) {
      $embed_url = 'http://www.democracynow.org/embed/story/'
        . $video['codec']['matches'][1] . '/'
        . $video['codec']['matches'][2] . '/'
        . $video['codec']['matches'][3] . '/'
        . $video['codec']['matches'][4];
    }
    else {
      $embed_url = 'http://www.democracynow.org/embed/show/'
        . $video['codec']['matches'][1] . '/'
        . $video['codec']['matches'][2] . '/'
        . $video['codec']['matches'][3];
    }
    return [
      'src' => $embed_url,
      'properties' => [
        'allowfullscreen' => 'true',
      ],
    ];
  }

}
