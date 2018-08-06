<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides GameTrailers codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "game_trailers",
 *   name = @Translation("Game Trailers"),
 *   example_url = "http://www.gametrailers.com/video/some-title/12345",
 *   regexp = {
 *     "/gametrailers\.com\/player\/([0-9]+)/",
 *     "/gametrailers\.com\/video\/([a-z0-9\-_]+)\/([0-9]+)/",
 *   },
 *   ratio = "16/9",
 * )
 */
class GameTrailers extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function flash($video) {
    if (is_numeric($video['codec']['matches'][1])) {
      $match = $video['codec']['matches'][1];
    }
    elseif (is_numeric($video['codec']['matches'][2])) {
      $match = $video['codec']['matches'][2];
    }
    return [
      'src' => '//media.mtvnservices.com/embed/mgid:moses:video:gametrailers.com:' . $match,
    ];
  }

}
