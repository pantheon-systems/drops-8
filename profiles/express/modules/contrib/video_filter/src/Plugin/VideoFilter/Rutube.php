<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides Rutube codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "rutube",
 *   name = @Translation("Rutube"),
 *   example_url = "http://rutube.ru/video/c80617086143e80ee08f760a2e9cbf43/?pl_type=source&pl_id=8188",
 *   regexp = {
 *     "/rutube\.ru\/(.*)/i",
 *   },
 *   ratio = "16/9",
 * )
 */
class Rutube extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function iframe($video) {
    $attributes = [
      'skinColor' => (isset($video['skinColor']) && !empty($video['standardColor'])) ? 'skinColor=' . (string) $video['skinColor'] : '',
      'sTitle' => (isset($video['sTitle']) && $video['sTitle'] == 1) ? 'sTitle=true' : 'sTitle=false',
      'sAuthor' => (isset($video['sAuthor']) && $video['sAuthor'] == 1) ? 'sAuthor=true' : 'sAuthor=false',
      'bmstart' => (isset($video['bmstart']) && $video['bmstart'] > 1) ? 'bmstart=' . (int) $video['bmstart'] : 'bmstart=false',
    ];
    $endpoint = 'http://rutube.ru/api/oembed/?url=' . $video['source'] . '&format=json';
    $request = \Drupal::httpClient()->get($endpoint, ['headers' => ['Accept' => 'application/json']]);
    if ($request->getStatusCode() == 200) {
      $response = json_decode($request->getBody());
    }
    if (!empty($response->html)) {
      if (preg_match('/src="([^"]+)"/', $response->html, $match)) {
        return [
          'src' => $match[1] . '?' . implode('&', $attributes),
          'properties' => [
            'webkitAllowFullScreen' => 'true',
            'mozallowfullscreen' => 'true',
            'allowfullscreen' => 'true',
          ],
        ];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function options() {
    $form = parent::options();
    $form['sTitle'] = [
      '#title' => $this->t('Show video title'),
      '#type' => 'checkbox',
    ];
    $form['sAuthor'] = [
      '#title' => $this->t('Show author name'),
      '#type' => 'checkbox',
    ];
    $form['bmstart'] = [
      '#title' => $this->t('Start time (optional)'),
      '#type' => 'textfield',
      '#description' => $this->t('Values is in seconds.'),
    ];
    $form['standardColor'] = [
      '#title' => $this->t('Use standard player color'),
      '#type' => 'checkbox',
      '#default_value' => TRUE,
    ];
    $form['skinColor'] = [
      '#title' => $this->t('Color (optional)'),
      '#type' => 'textfield',
      '#description' => $this->t('Only Hexadecimal color codes allowed. Do not include <strong>#</strong>. For example: <strong>FF0000</strong>.'),
      '#states' => [
        'invisible' => [
          ':input[name="options[rutube][options][standardColor]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    return $form;
  }

}
