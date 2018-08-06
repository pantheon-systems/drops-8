<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides Whatchado codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "whatchado",
 *   name = @Translation("Whatchado"),
 *   example_url = "https://www.whatchado.com/de/stories/my-story-title",
 *   regexp = {
 *     "/whatchado\.com\/([a-z]{2})\/([a-zA-Z0-9-]+)\/([\w-_]+)/i",
 *     "/whatchado\.com\/([a-z]{2})\/([\w-_]+)/i",
 *   },
 *   ratio = "960/540",
 * )
 */
class Whatchado extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function iframe($video) {
    if (!empty($video['codec']['matches'][3])) {
      $video_url = 'https://www.whatchado.com/'
        . $video['codec']['matches'][1] . '/'
        . $video['codec']['matches'][2]
        . '/embed/'
        . $video['codec']['matches'][3];
    }
    else {
      $video_url = 'https://www.whatchado.com/'
        . $video['codec']['matches'][1] .
        '/stories/embed/'
        . $video['codec']['matches'][2];
    }
    return [
      'src' => $video_url,
      'properties' => [
        'allowfullscreen' => 'true',
      ],
    ];
  }

}
