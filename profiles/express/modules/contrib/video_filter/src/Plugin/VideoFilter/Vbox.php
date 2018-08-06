<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides Vbox codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "vbox",
 *   name = @Translation("Vbox"),
 *   example_url = "http://vbox7.com/play:b4a7291f3d",
 *   regexp = {
 *     "/vbox7\.com\/play\:([a-z0-9]+)/i",
 *   },
 *   ratio = "400/345",
 * )
 */
class Vbox extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function flash($video) {
    return [
      'src' => '//vbox7.com/emb/external.php?vid=' . $video['codec']['matches'][1],
    ];
  }

}
