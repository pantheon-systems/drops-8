<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides Capped codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "capped",
 *   name = @Translation("Capped"),
 *   example_url = "http://capped.tv/playeralt.php?vid=some-title",
 *   regexp = {
 *     "/capped\.tv\/([a-zA-Z0-9\-_]+)/",
 *   },
 *   ratio = "425/355",
 * )
 */
class Capped extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function flash($video) {
    return [
      'src' => 'http://capped.micksam7.com/playeralt.swf?vid=' . $video['codec']['matches'][1],
    ];
  }

}
