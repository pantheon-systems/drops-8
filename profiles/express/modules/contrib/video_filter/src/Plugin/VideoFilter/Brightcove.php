<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides Brightcove codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "brightcove",
 *   name = @Translation("Brightcove"),
 *   example_url = "https://players.brightcove.net/12345/f150a402-ecea-40c6-9ae4-a4c854694835_default/index.html?videoId=12345",
 *   regexp = {
 *     "/(http\:|https\:)?\/\/players\.brightcove\.net\/[0-9]+\/[a-zA-Z0-9\-_&;]+\/index\.html\?videoId=[0-9]+/"
 *   },
 *   ratio = "16/9"
 * )
 */
class Brightcove extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function html($video) {
    $html = '';

    $video_url = !empty($video['codec']['matches'][0]) ? $video['codec']['matches'][0] : '';

    if (!empty($video_url)) {
      $html .= '<div style="display: block; position: relative; max-width: 500px;">';
      $html .= '<div style="padding-top: 56.25%;">';
      $html .= '<iframe frameborder="0" src="' . $video_url . '"';
      $html .= ' allowfullscreen webkitallowfullscreen mozallowfullscreen';
      $html .= ' style="width: 100%; height: 100%; position: absolute;';
      $html .= ' top: 0px; bottom: 0px; right: 0px; left: 0px;"></iframe>';
      $html .= '</div></div>';
    }

    return $html;

  }

}
