<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides FlickrSlideshows codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "flickr_slideshows",
 *   name = @Translation("Flickr Slideshows"),
 *   example_url = "http://www.flickr.com/photos/username/sets/1234567890/show/",
 *   regexp = {
 *     "/flickr\.com\/photos\/([a-zA-Z0-9@_\-]+)\/sets\/([0-9]+)\/?[show]?\/?/i",
 *   },
 *   ratio = "4/3",
 *   control_bar_height = 0,
 * )
 */
class FlickrSlideshows extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function flash($video) {
    $user_name = $video['codec']['matches'][1];
    $set_id = $video['codec']['matches'][2];
    return [
      'src' => "//www.flickr.com/apps/slideshow/show.swf?v=67348" . ($video['autoplay'] ? '&amp;autoplay=1' : ''),
      'params' => [
        'flashvars' => "&amp;offsite=true&amp;lang=en-us&amp;page_show_url=%2Fphotos%2F$user_name%2Fsets%2F$set_id%2Fshow%2F&amp;page_show_back_url=%2Fphotos%2F$user_name%2Fsets%2F$set_id%2F&amp;set_id=$set_id&amp;jump_to=",
      ],
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
