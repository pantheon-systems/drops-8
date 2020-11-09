<?php

namespace Drupal\metatag_test_custom_route\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Testing routes for Metatag tests.
 */
class MetatagTestCustomRouteController extends ControllerBase {

  /**
   * Constructs a page for integration testing.
   */
  public function test() {
    $render = [
      '#markup' => $this->t('<p>Hello world!</p>'),
    ];

    return $render;
  }

}
