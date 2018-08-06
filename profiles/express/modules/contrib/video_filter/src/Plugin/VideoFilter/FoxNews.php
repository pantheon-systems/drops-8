<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides FoxNews codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "foxnews",
 *   name = @Translation("FoxNews"),
 *   example_url = "http://video.foxnews.com/v/123456/the-title/",
 *   regexp = {
 *     "/video\.foxnews\.com\/v\/([0-9]+)\/([a-zA-Z0-9\-]+)/i",
 *   },
 *   ratio = "466/263",
 * )
 */
class FoxNews extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function html($video) {
    $video_id = $video['codec']['matches'][1];
    $html = '<script type="text/javascript" src="http://video.foxnews.com/v/embed.js?id=' . $video_id . '&w=' . $video['width'] . '&h=' . $video['height'] . '"></script>';
    return $html;
  }

}
