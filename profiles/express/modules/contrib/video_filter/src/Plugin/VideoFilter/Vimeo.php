<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides Vimeo codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "vimeo",
 *   name = @Translation("Vimeo"),
 *   example_url = "https://vimeo.com/25290459",
 *   regexp = {
 *     "/vimeo\.com\/([0-9]+)/",
 *     "/vimeo\.com\/(.*)\/([0-9]+)/i",
 *   },
 *   ratio = "16/9",
 *   control_bar_height = 0
 * )
 */
class Vimeo extends VideoFilterBase {

  /**
   * Video embed tag attributes.
   */
  protected function attributes($video) {
    $attributes = [
      'autopause' => isset($video['autopause']) ? 'autopause=' . (int) $video['autopause'] : 'autopause=1',
      'autoplay' => isset($video['autoplay']) ? 'autoplay=' . (int) $video['autoplay'] : 'autoplay=0',
      'badge' => isset($video['badge']) ? 'badge=' . (int) $video['badge'] : 'badge=1',
      'byline' => isset($video['byline']) ? 'byline=' . (int) $video['byline'] : 'byline=1',
      'loop' => isset($video['loop']) ? 'loop=' . (int) $video['loop'] : 'loop=0',
      'portrait' => isset($video['portrait']) ? 'portrait=' . (int) $video['portrait'] : 'portrait=1',
      'title' => isset($video['title']) ? 'autopause=' . (int) $video['title'] : 'autopause=1',
      'fullscreen' => isset($video['fullscreen']) ? 'fullscreen=' . (int) $video['fullscreen'] : 'fullscreen=1',
    ];
    if (!empty($video['color'])) {
      $attributes['color'] = (string) $video['color'];
    }
    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function iframe($video) {
    $video_id = !empty($video['codec']['matches'][1]) ? (int) $video['codec']['matches'][1] : '';
    if (empty($video_id)) {
      // Support for editor picks video embed.
      $video_id = !empty($video['codec']['matches'][2]) ? (int) $video['codec']['matches'][2] : '';
    }
    return [
      'src' => '//player.vimeo.com/video/' . $video_id . '?' . implode('&', $this->attributes($video)),
      'properties' => [
        'webkitallowfullscreen' => 'true',
        'mozallowfullscreen' => 'true',
        'allowfullscreen' => 'true',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function flash($video) {
    $attributes = $this->attributes($video);
    $attributes['clip_id'] = $video['codec']['matches'][1];
    return [
      'src' => '//www.vimeo.com/moogaloop.swf?' . implode('&amp;', $attributes),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function options() {
    $form = parent::options();
    $form['autopause'] = [
      '#title' => $this->t('Enable pausing this video when another video is played.'),
      '#type' => 'checkbox',
    ];
    $form['autoplay'] = [
      '#title' => $this->t('Autoplay'),
      '#type' => 'checkbox',
    ];
    $form['badge'] = [
      '#title' => $this->t('Enable the badge on the video.'),
      '#type' => 'checkbox',
    ];
    $form['byline'] = [
      '#title' => $this->t('Show the user’s byline on the video.'),
      '#type' => 'checkbox',
    ];
    $form['loop'] = [
      '#title' => $this->t('Play the video again when it reaches the end.'),
      '#type' => 'checkbox',
    ];
    $form['portrait'] = [
      '#title' => $this->t('Show the user’s portrait on the video.'),
      '#type' => 'checkbox',
    ];
    $form['title'] = [
      '#title' => $this->t('Show the title on the video.'),
      '#type' => 'checkbox',
    ];
    $form['fullscreen'] = [
      '#title' => $this->t('Allow the player to go into fullscreen.'),
      '#type' => 'checkbox',
    ];
    $form['color'] = [
      '#title' => $this->t('Color (optional)'),
      '#type' => 'textfield',
      '#description' => $this->t('Specify the color of the video controls. Defaults to 00adef. Make sure that you don’t include the #.'),
    ];
    return $form;
  }

}
