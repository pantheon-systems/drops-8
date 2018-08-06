<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides GoogleVideo codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "google_video",
 *   name = @Translation("Google Video"),
 *   example_url = "http://video.google.com/videoplay?docid=-uN1qUeId",
 *   regexp = {
 *     "/video\.google\.[a-z]+\.?[a-z]+?\/videoplay\?docid=(\-?[0-9]+)/",
 *   },
 *   ratio = "400/326",
 * )
 */
class GoogleVideo extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function flash($video) {
    return [
      'src' => '//video.google.com/googleplayer.swf?docId=' . $video['codec']['matches'][1],
    ];
  }

}
