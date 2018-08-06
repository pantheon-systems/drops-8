<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides YouKu codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "youku",
 *   name = @Translation("YouKu"),
 *   example_url = "http://v.youku.com/v_show/id_XNzE1NTMyMDUy.html",
 *   regexp = {
 *     "/youku\.com\/v_show\/id_([a-z0-9\-_=]+)\.html/i",
 *     "/youku\.com\/player\.php\/sid\/([a-z0-9\-_]+)/i",
 *   },
 *   ratio = "16/9",
 *   control_bar_height = 50
 * )
 */
class YouKu extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function iframe($video) {
    $attributes = [
      'autoplay' => !empty($video['autoplay']) ? 'autoplay=1' : 'autoplay=0',
    ];
    return [
      'src' => 'http://player.youku.com/embed/' . $video['codec']['matches'][1] . '?' . implode('&amp;', $attributes),
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
