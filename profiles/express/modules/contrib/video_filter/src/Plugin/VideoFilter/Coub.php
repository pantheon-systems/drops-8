<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides Coub codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "coub",
 *   name = @Translation("Coub"),
 *   example_url = "http://coub.com/view/b7ghv",
 *   regexp = {
 *     "/coub\.com\/view\/([a-z0-9]+)/i",
 *   },
 *   ratio = "4/3",
 * )
 */
class Coub extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function iframe($video) {
    $attributes = [
      'autostart' => !empty($video['autoplay']) ? 'autoplay=true' : 'autoplay=false',
      'originalSize' => !empty($video['originalSize']) ? 'originalSize=true' : 'originalSize=false',
      'startWithHD' => !empty($video['startWithHD']) ? 'startWithHD=true' : 'startWithHD=false',
      'muted' => !empty($video['muted']) ? 'muted=true' : 'muted=false',
    ];
    return [
      'src' => '//coub.com/embed/' . $video['codec']['matches'][1] . '?' . implode('&', $attributes),
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
      '#title' => $this->t('Autoplay (optional)'),
      '#type' => 'checkbox',
    ];
    $form['originalSize'] = [
      '#title' => $this->t('Show original size (optional)'),
      '#type' => 'checkbox',
    ];
    $form['startWithHD'] = [
      '#title' => $this->t('Start with HD (optional)'),
      '#type' => 'checkbox',
    ];
    $form['muted'] = [
      '#title' => $this->t('Muted (optional)'),
      '#type' => 'checkbox',
    ];
    return $form;
  }

}
