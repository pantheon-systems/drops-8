<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides TED codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "ted",
 *   name = @Translation("TED"),
 *   example_url = "//www.ted.com/talks/[story-title]",
 *   regexp = {
 *     "/ted\.com\/talks\/lang\/([a-zA-Z]+)\/([a-zA-Z0-9\-_]+)(\.html)?/",
 *     "/ted\.com\/talks\/([a-zA-Z0-9\-_]+)(\.html)?/",
 *   },
 *   ratio = "4/3",
 * )
 */
class TED extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function instructions() {
    return $this->t('Click in Embed and copy the link -Link to this talk- and paste here.');
  }

  /**
   * {@inheritdoc}
   */
  public function iframe($video) {
    $video_id = $video['codec']['matches'][3];
    if (empty($video_id)) {
      $video_id = $video['codec']['matches'][1];
    }
    return [
      'src' => '//embed-ssl.ted.com/talks/' . $video_id . '.html',
      'properties' => [
        'webkitAllowFullScreen' => 'true',
        'mozallowfullscreen' => 'true',
        'allowFullScreen' => 'true',
        'scrolling' => 'no',
      ],
    ];
  }

}
