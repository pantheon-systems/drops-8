<?php

namespace Drupal\video_filter\Plugin\VideoFilter;

use Drupal\video_filter\VideoFilterBase;

/**
 * Provides ArchiveOrg codec for Video Filter.
 *
 * @VideoFilter(
 *   id = "archive_org",
 *   name = @Translation("Archive.org"),
 *   example_url = "http://www.archive.org/details/DrupalconBoston2008-TheStateOfDrupal",
 *   regexp = {
 *     "/archive\.org\/details\/([\w-_\.]+)/i",
 *   },
 *   ratio = "4/3",
 * )
 */
class ArchiveOrg extends VideoFilterBase {

  /**
   * {@inheritdoc}
   */
  public function iframe($video) {
    return [
      'src' => '//www.archive.org/embed/' . $video['codec']['matches'][1],
    ];
  }

}
