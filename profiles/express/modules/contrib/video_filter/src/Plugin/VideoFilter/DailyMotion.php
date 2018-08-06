<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides DailyMotion codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "daily_motion",
 *   name = @Translation("DailyMotion"),
 *   example_url = "http://www.dailymotion.com/video/some_video_title",
 *   regexp = {
 *     "/dailymotion\.com\/video\/([a-z0-9\-_]+)/i",
 *   },
 *   ratio = "4/3",
 *   control_bar_height = 20,
 * )
 */
class DailyMotion extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function iframe($video) {
    $attributes = [
      'autoplay' => $video['autoplay'] ? 'autoplay=1' : 'autoplay=0',
    ];
    return [
      'src' => '//www.dailymotion.com/embed/video/' . $video['codec']['matches'][1] . '?' . implode('&amp;', $attributes),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function flash($video) {
    $attributes = [
      'autoplay' => $video['autoplay'] ? 'autoplay=1' : 'autoplay=0',
    ];
    return [
      'src' => '//www.dailymotion.com/swf/' . $video['codec']['matches'][1] . '?' . implode('&amp;', $attributes),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function options() {
    $form = parent::options();
    $form['autoplay'] = [
      '#title' => $this->t('Autoplay (optional)'),
      '#type' => 'checkbox',
    ];
    return $form;
  }

}
