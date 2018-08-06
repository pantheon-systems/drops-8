<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides Facebook codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "facebook",
 *   name = @Translation("Facebook"),
 *   example_url = "//www.facebook.com/video.php?v=10152795258318553",
 *   regexp = {
 *     "/facebook\.com\/video\.php\?v=([0-9]+)/i",
 *   },
 *   ratio = "1280/720",
 * )
 */
class Facebook extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function iframe($video) {
    return [
      'src' => '//www.facebook.com/video/embed?video_id=' . $video['codec']['matches'][1],
      'properties' => [
        'allowfullscreen' => 'true',
      ],
    ];
  }

}
