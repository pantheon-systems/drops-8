<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides Teachertube codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "teachertube",
 *   name = @Translation("Teachertube"),
 *   example_url = "http://www.teachertube.com/video/saes-lemonade-war-chapter-13-[video-id]",
 *   regexp = {
 *     "/teachertube\.com\/video\/([a-z0-9\-]+)\-([0-9]+)/i",
 *   },
 *   ratio = "16/9",
 * )
 */
class Teachertube extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function iframe($video) {
    return [
      'src' => '//www.teachertube.com/embed/video/' . $video['codec']['matches'][2],
      'properties' => [
        'allowfullscreen' => 'true',
      ],
    ];
  }

}
