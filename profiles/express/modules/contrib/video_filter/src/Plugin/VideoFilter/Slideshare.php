<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides Slideshare codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "slideshare",
 *   name = @Translation("Slideshare"),
 *   example_url = "http://slideshare.net/1759622",
 *   regexp = {
 *     "/slideshare\.net\/\?id=([a-z0-9]+)/",
 *     "/slideshare\.net\/([a-z0-9]+)/",
 *   },
 *   ratio = "425/355",
 * )
 */
class Slideshare extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function instruction() {
    return $this->t('You need to construct your own URL, using the "Wordpress Embed" code from Slideshare, extract the "id", and form the URL like this: slideshare.net/1759622');
  }

  /**
   * {@inheritdoc}
   */
  public function iframe($video) {
    return [
      'src' => '//www.slideshare.net/slideshow/embed_code/' . $video['codec']['matches'][1],
    ];
  }

}
