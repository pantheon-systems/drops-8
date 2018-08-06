<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides Vine codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "vine",
 *   name = @Translation("Vine"),
 *   example_url = "https://vine.co/v/inIKA3LaWUe",
 *   regexp = {
 *     "/vine\.co\/v\/([0-9a-z]+)/i",
 *   },
 *   ratio = "4/3",
 * )
 */
class Vine extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function iframe($video) {
    return [
      'src' => '//vine.co/v/' . $video['codec']['matches'][1] . '/embed/simple',
      'properties' => [],
    ];
  }

}
