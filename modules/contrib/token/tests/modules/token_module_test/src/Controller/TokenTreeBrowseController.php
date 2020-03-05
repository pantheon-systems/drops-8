<?php

namespace Drupal\token_module_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

class TokenTreeBrowseController extends ControllerBase {

  /**
   * Page callback to output a link.
   */
  function outputLink(Request $request) {
    $build['tree']['#theme'] = 'token_tree_link';
    $build['tokenarea'] = [
      '#markup' => \Drupal::token()->replace('[current-page:title]'),
      '#type' => 'markup',
    ];
    return $build;
  }

  /**
   * Title callback for the page outputting a link.
   *
   * We are using a title callback instead of directly defining the title in the
   * routing YML file. This is so that we could return an array instead of a
   * simple string. This allows us to test if [current-page:title] works with
   * render arrays and other objects as titles.
   */
  public function getTitle() {
    return [
      '#type' => 'markup',
      '#markup' => 'Available Tokens',
    ];
  }

}
