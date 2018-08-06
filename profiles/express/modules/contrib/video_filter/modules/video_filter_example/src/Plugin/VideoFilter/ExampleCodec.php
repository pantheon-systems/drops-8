<?php

namespace Drupal\video_filter_example\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides ExampleCodec codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "example",
 *   name = @Translation("Example"),
 *   example_url = "https://www.drupal.org/project/video_filter",
 *   regexp = {
 *     "/drupal\.org\/project\/([0-9a-z_]+)/i",
 *   },
 *   ratio = "4/3",
 * )
 */
class ExampleCodec extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function iframe($video) {
    return [
      'src' => '//www.drupal.org/project/' . $video['codec']['matches'][1],
      'properties' => [
        'allowfullscreen' => 'true',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function flash($video) {
    // See Youtube.php for implementation example.
    // or any other plugins.
  }

  /**
   * {@inheritdoc}
   */
  public function html($video) {
    // HTML code of the video. In case if you would like to use oembed.
  }

  /**
   * {@inheritdoc}
   */
  public function options() {
    $form['width'] = [
      '#title' => $this->t('Width (optional)'),
      '#type' => 'textfield',
    ];
    $form['height'] = [
      '#title' => $this->t('Height (optional)'),
      '#type' => 'textfield',
    ];
    return $form;
  }

}
