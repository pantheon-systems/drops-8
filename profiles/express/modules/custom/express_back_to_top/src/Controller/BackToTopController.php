<?php 
/**
 * @file
 * Contains \Drupal\express_back_to_top\Controller\BackToTopController.
 */

namespace Drupal\express_back_to_top\Controller;

use Drupal\Core\Controller\ControllerBase;

class BackToTopController extends ControllerBase {
  public function content() {
    return array(
      '#type' => 'markup',
      '#markup' => $this->t('<div id="express_back_to_top"><a href="#page" title="Back to Top">Back to Top</a></div>'),
    );
  }
}
