<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides FlickrVideo codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "flickr_video",
 *   name = @Translation("Flickr Video"),
 *   example_url = "http://www.flickr.com/photos/hansnilsson/1234567890/",
 *   regexp = {
 *     "/flickr\.com\/photos\/([a-zA-Z0-9@_\-]+)\/([0-9]+)/",
 *   },
 *   ratio = "4/3",
 *   control_bar_height = 0,
 * )
 */
class FlickrVideo extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function flash($video) {
    return [
      'src' => '//www.flickr.com/apps/video/stewart.swf?v=1.161',
      'params' => [
        'flashvars' => '&amp;photo_id=' . $video['codec']['matches'][2] . '&amp;flickr_show_info_box=true',
      ],
    ];
  }

}
