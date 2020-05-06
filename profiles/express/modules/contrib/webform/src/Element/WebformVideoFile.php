<?php

namespace Drupal\webform\Element;

/**
 * Provides a webform element for an 'video_file' element.
 *
 * @FormElement("webform_video_file")
 */
class WebformVideoFile extends WebformManagedFileBase {

  /**
   * {@inheritdoc}
   */
  protected static $accept = 'video/mp4,video/x-m4v,video/*';

}
