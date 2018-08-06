<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides Vevo codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "vevo",
 *   name = @Translation("Vevo"),
 *   example_url = "http://www.vevo.com/watch/USUV71600451",
 *   regexp = {
 *     "/vevo\.com\/watch\/(.*)\/([\w-_]+)/i",
 *   },
 *   ratio = "16/9",
 *   control_bar_height = 0
 * )
 */
class Vevo extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function iframe($video) {
    $attributes = [
      'video' => 'video=' . $video['codec']['matches'][2],
      'autoplay' => !empty($video['autoplay']) ? 'autoplay=1' : 'autoplay=0',
    ];
    return [
      'src' => 'http://cache.vevo.com/assets/html/embed.html?' . implode('&', $attributes),
      'properties' => [
        'allowfullscreen' => 'true',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function options() {
    $form = parent::options();
    $form['autoplay'] = [
      '#title' => $this->t('Autoplay'),
      '#type' => 'checkbox',
    ];
    return $form;
  }

}
