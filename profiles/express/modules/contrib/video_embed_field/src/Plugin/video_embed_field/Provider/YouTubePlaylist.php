<?php

namespace Drupal\video_embed_field\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * A YouTube playlist video provider.
 *
 * @VideoEmbedProvider(
 *   id = "youtube_playlist",
 *   title = @Translation("YouTube Playlist")
 * )
 */
class YouTubePlaylist extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    return [
      '#type' => 'video_embed_iframe',
      '#provider' => 'youtube_playlist',
      '#url' => 'https://www.youtube.com/embed/videoseries',
      '#query' => [
        'list' => $this->getVideoId(),
      ],
      '#attributes' => [
        'width' => $width,
        'height' => $height,
        'frameborder' => '0',
        'allowfullscreen' => 'allowfullscreen',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    return sprintf('http://img.youtube.com/vi/%s/hqdefault.jpg', static::getUrlComponent($this->getInput(), 'video_id'));
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    return static::getUrlComponent($input, 'id');
  }

  /**
   * Get a component from the URL.
   *
   * @param string $input
   *   The input URL.
   * @param string $component
   *   The component from the regex to get.
   *
   * @return string
   *   The value of the match in the regex.
   */
  protected static function getUrlComponent($input, $component) {
    preg_match('/^https?:\/\/(?:www\.)?youtube\.com\/watch\?(?=.*v=(?<video_id>[0-9A-Za-z_-]*))(?=.*list=(?<id>[A-Za-z0-9_-]*))/', $input, $matches);
    return isset($matches[$component]) ? $matches[$component] : FALSE;
  }

}
