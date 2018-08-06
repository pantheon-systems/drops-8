<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides Twitch codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "twitch",
 *   name = @Translation("Twitch"),
 *   example_url = "http://www.twitch.tv/uN1qUe-I_d",
 *   regexp = {
 *     "/twitch\.tv\/((?!directory)[a-z0-9\-_]+)\/b\/([\d]+)/i",
 *     "/twitch\.tv\/((?!directory)[a-z0-9\-_]+)/i",
 *   },
 *   ratio = "16/9",
 *   control_bar_height = 30,
 * )
 */
class Twitch extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function iframe($video) {
    $attributes = [
      'autoplay' => !empty($video['autoplay']) ? 'autoplay=1' : 'autoplay=0',
      'time' => !empty($video['time']) ? 'time=' . $video['time'] : '',
    ];
    return [
      'src' => '//player.twitch.tv/?channel=' . $video['codec']['matches'][1],
      'parameters' => [
        'scrolling' => 'no',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function options() {
    $form = parent::options();
    $form['time'] = [
      '#title' => $this->t('Time (optional)'),
      '#type' => 'textfield',
      '#description' => $this->t('Start time'),
    ];
    $form['autoplay'] = [
      '#title' => $this->t('Autoplay (optional)'),
      '#type' => 'checkbox',
    ];
    return $form;
  }

}
