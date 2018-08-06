<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides Giphy codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "giphy",
 *   name = @Translation("Giphy"),
 *   example_url = "http://giphy.com/gifs/disney-kids-peter-pan-[gif-id]",
 *   regexp = {
 *     "/giphy\.com\/gifs\/(([a-zA-Z0-9\-]+)\-|)([a-zA-Z0-9]+)/i",
 *   },
 *   ratio = "16/9",
 * )
 */
class Giphy extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function iframe($video) {
    return [
      'src' => '//giphy.com/embed/' . $video['codec']['matches'][3],
      'properties' => [
        'allowfullscreen' => 'true',
      ],
    ];
  }

}
