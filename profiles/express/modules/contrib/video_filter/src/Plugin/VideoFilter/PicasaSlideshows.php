<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides PicasaSlideshows codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "picasa_slideshows",
 *   name = @Translation("Picasa Slideshows"),
 *   example_url = "http://picasaweb.google.com/data/feed/base/user/USER_NAME/albumid/5568104935784209834?alt=rss&amp;kind=photo&amp;hl=en_US",
 *   regexp = {
 *     "/picasaweb\.google\.com\/data\/feed\/base\/user\/([a-zA-Z0-9@_\-\.]+)\/albumid\/([a-z0-9]+)/i",
 *   },
 *   ratio = "800/600",
 * )
 */
class PicasaSlideshows extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function instructions() {
    return $this->t('You must use the URL of the RSS feed for the <strong>Picasa album</strong>:') .
      '<ol>
      <li>' . $this->t('View the album in Picasa (you should see thumbnails, not a slideshow).') . '</li>
      <li>' . $this->t('Find the "RSS" link and click it.') . '</li>
      <li>' . $this->t('Copy the resulting URL from the browser address bar.  Example:') . '<br />
      <code>[video: http://picasaweb.google.com/data/feed/base/user/USER_NAME/albumid/5568104935784209834?alt=rss&amp;kind=photo&amp;hl=en_US]</code>
      </li>
      </ol>';
  }

  /**
   * {@inheritdoc}
   */
  public function flash($video) {
    $user_name = $video['codec']['matches'][1];
    $set_id = $video['codec']['matches'][2];
    return [
      'src' => '//picasaweb.google.com/s/c/bin/slideshow.swf',
      'params' => [
        'flashvars' => "host=picasaweb.google.com&amp;&amp;feat=flashalbum&amp;RGB=0x000000&amp;feed=http%3A%2F%2Fpicasaweb.google.com%2Fdata%2Ffeed%2Fapi%2Fuser%2F" . $user_name . "%2Falbumid%2F" . $set_id . "%3Falt%3Drss%26kind%3Dphoto%26" . ($video['autoplay'] ? '' : '&amp;noautoplay=1'),
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
