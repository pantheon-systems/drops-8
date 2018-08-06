<?php

namespace Drupal\video_filter\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Video Filter module routes.
 */
class VideoFilter extends ControllerBase {

  /**
   * Generate preview HTML for a [video] token in CKEditor.
   */
  public function preview($format = '', $token = '') {
    echo $token;
    exit;
  }

}
