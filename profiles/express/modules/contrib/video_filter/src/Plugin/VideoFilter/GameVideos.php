<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides GameVideos codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "game_videos",
 *   name = @Translation("Game Videos"),
 *   example_url = "http://gamevideos.1up.com/video/id/12345",
 *   regexp = {
 *     "/gamevideos\.1up\.com\/video\/id\/([0-9]+)/",
 *   },
 *   ratio = "500/319",
 * )
 */
class GameVideos extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function flash($video) {
    return [
      'src' => '//gamevideos.1up.com/swf/gamevideos12.swf?embedded=1&amp;fullscreen=1&amp;autoplay=' . (!empty($video['autoplay']) ? '1' : '0') . '&amp;src=http://gamevideos.1up.com/do/videoListXML%3Fid%3D' . $video['codec']['matches'][1],
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
    return $form;
  }

}
