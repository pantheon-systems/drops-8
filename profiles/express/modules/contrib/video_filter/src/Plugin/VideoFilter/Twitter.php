<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides Twitter codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "twitter",
 *   name = @Translation("Twitter"),
 *   example_url = "https://twitter.com/minnur/status/705915261614821376",
 *   regexp = {
 *     "/twitter\.com\/(.*)\/status\/([0-9]+)/i",
 *   },
 * )
 */
class Twitter extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function html($video) {
    // Get embed code via oEmbed.
    $endpoint = 'https://api.twitter.com/1/statuses/oembed.json?url=https://twitter.com/' . $video['codec']['matches'][1] . '/status/' . $video['codec']['matches'][2];
    $request = \Drupal::httpClient()->get($endpoint, ['headers' => ['Accept' => 'application/json']]);
    if ($request->getStatusCode() == 200) {
      $response = json_decode($request->getBody());
    }
    $html = !empty($response->html) ? $response->html : '';
    return $html;
  }

  /**
   * {@inheritdoc}
   */
  public function options() {
    return [];
  }

}
