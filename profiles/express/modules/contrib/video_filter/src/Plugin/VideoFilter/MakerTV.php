<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides MakerTV (Previously Blip.TV) codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "maker_tv",
 *   name = @Translation("MakerTV"),
 *   example_url = "http://blip.tv/file/123456",
 *   regexp = {
 *     "/maker\.tv\/video\/([a-z0-9]+)/i",
 *     "/maker\.tv\/([a-z0-9]+)\/([a-z0-9]+)\/video\/([a-z0-9]+)/i",
 *   },
 *   ratio = "16/9",
 *   control_bar_height = 30,
 * )
 */
class MakerTV extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function iframe($video) {
    $video_id = !empty($video['codec']['matches'][1]) ? $video['codec']['matches'][1] : '';
    if (empty($video_id)) {
      $video_id = !empty($video['codec']['matches'][3]) ? $video['codec']['matches'][3] : '';
    }
    return [
      'src' => 'http://makerplayer.com/embed/maker/' . $video_id,
      'properties' => [
        'allowfullscreen' => 'true',
        'seamless' => 'true',
        'scrolling' => 'no',
      ],
    ];
  }

}
