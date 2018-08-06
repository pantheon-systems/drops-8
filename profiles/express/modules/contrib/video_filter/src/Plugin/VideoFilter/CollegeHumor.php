<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides CollegeHumor codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "college_humor",
 *   name = @Translation("College Humor"),
 *   example_url = "http://www.collegehumor.com/video:1234567890",
 *   regexp = {
 *     "/collegehumor\.com\/video\:([0-9]+)/",
 *   },
 *   ratio = "16/9",
 *   control_bar_height = 0,
 * )
 */
class CollegeHumor extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function flash($video) {
    return [
      'src' => 'http://www.collegehumor.com/moogaloop/moogaloop.swf?clip_id=' . $video['codec']['matches'][1] . '&amp;fullscreen=1',
    ];
  }

}
