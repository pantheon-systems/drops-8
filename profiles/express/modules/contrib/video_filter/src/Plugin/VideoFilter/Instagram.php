<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides Instagram codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "instagram",
 *   name = @Translation("Instagram"),
 *   example_url = "//www.instagram.com/p/BB-VhgbENtG/",
 *   regexp = {
 *     "/instagram\.com\/p\/([a-z0-9\-_]+)/i",
 *     "/instagr.am\/p\/([a-z0-9\-_]+)/i",
 *   },
 *   ratio = "612/710",
 * )
 */
class Instagram extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function html($video) {
    // Get embed code via oEmbed.
    $endpoint = 'https://api.instagram.com/oembed?url=//instagr.am/p/' . $video['codec']['matches'][1];
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
