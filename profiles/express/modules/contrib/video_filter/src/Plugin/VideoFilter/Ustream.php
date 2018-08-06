<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides Ustream codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "ustream",
 *   name = @Translation("Ustream"),
 *   example_url = "http://www.ustream.tv/recorded/111212121212",
 *   regexp = {
 *     "/ustream\.tv\/recorded\/([0-9]+)/i",
 *   },
 *   ratio = "16/9",
 * )
 */
class Ustream extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function iframe($video) {
    $attributes = [
      'html5ui' => 'html5ui',
      'autoplay' => isset($video['autoplay']) ? 'autoplay=' . (int) $video['autoplay'] : 'autoplay=0',
    ];
    return [
      'src' => '//www.ustream.tv/embed/recorded/' . $video['codec']['matches'][1] . '?' . implode('&', $attributes),
      'properties' => [
        'allowfullscreen' => 'true',
        'webkitallowfullscreen' => 'true',
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
