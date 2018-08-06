<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides Wistia codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "wistia",
 *   name = @Translation("Wistia"),
 *   example_url = "http://wistia.com/medias/9pj9n6ftlk",
 *   regexp = {
 *     "@https?://(.+\.)?(wistia\.(com|net)|wi\.st)/((m|medias|projects)|embed/(iframe|playlists))/([a-zA-Z0-9]+)@",
 *   },
 *   ratio = "4/3",
 * )
 */
class Wistia extends VideoFilterBase {

  // NEED TO TEST. NOT SURE IF THIS WORKS.

  /**
   * {@inheritdoc}
   */
  public function html($video) {
    $video_code = $video['codec']['matches'][7];
    $matches = $video['codec']['matches'];
    $embed_type = ($matches[4] == 'projects' || $matches[6] == 'playlists') ? 'playlists' : 'iframe';

    // Get embed code via oEmbed.
    $endpoint = 'http://fast.wistia.com/oembed?url=http://fast.wistia.com/embed/' . $embed_type . '/' . $video_code . '&width=' . $video['width'] . '&height=' . $video['height'];
    $request = \Drupal::httpClient()->get($endpoint, ['headers' => ['Accept' => 'application/json']]);
    if ($request->getStatusCode() == 200) {
      $response = json_decode($request->getBody());
    }
    $html = !empty($response->html) ? $response->html : '';

    // See if the video source is already an iframe src.
    $pattern = '@https?://fast.wistia.(com|net)/embed/(iframe|playlists)/[a-zA-Z0-9]+\?+.+@';
    $matches = [];
    if (preg_match($pattern, $video['source'], $matches)) {
      // Replace the oEmbed iframe src with that provided in the token, in order
      // to support embed builder URLs.
      $pattern = '@https?://fast.wistia.(com|net)/embed/(iframe|playlists)/[a-zA-Z0-9]+\?[^"]+@';
      $replacement = $matches[0];
      $html = preg_replace($pattern, $replacement, $html);
    }
    return $html;
  }

}
