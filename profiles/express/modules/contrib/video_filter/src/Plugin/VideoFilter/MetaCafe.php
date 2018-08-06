<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides MetaCafe codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "google_video",
 *   name = @Translation("Google Video"),
 *   example_url = "http://www.metacafe.com/watch/1234567890/some_title/",
 *   regexp = {
 *     "/metacafe\.com\/watch\/([a-z0-9\-_]+)\/([a-z0-9\-_]+)/i",
 *   },
 *   ratio = "400/313",
 *   control_bar_height = 32,
 * )
 */
class MetaCafe extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function flash($video) {
    return [
      'src' => '//metacafe.com/fplayer/' . $video['codec']['matches'][1] . '/' . $video['codec']['matches'][2] . '.swf',
    ];
  }

}
