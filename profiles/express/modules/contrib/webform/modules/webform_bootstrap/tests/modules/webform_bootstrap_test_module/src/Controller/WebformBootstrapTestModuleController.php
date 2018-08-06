<?php

namespace Drupal\webform_bootstrap_test_module\Controller;

use Drupal\Core\Render\Markup;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route responses for Webform Test Bootstrap Helper.
 */
class WebformBootstrapTestModuleController extends ControllerBase {

  /**
   * Returns a Bootstrap style-guide.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   The webform submission webform.
   */
  public function styleguide(Request $request) {
    $style_guides = [
      'typography',
      'tables',
      'images',
      'icons',
      'media',
      'form',
      'inputs',
      'widgets',
    ];
    $build = [];
    foreach ($style_guides as $style_guide) {
      $content = file_get_contents(drupal_get_path('module', 'webform_bootstrap_test_module') . '/style-guide/' . $style_guide . '.html');
      $build[$style_guide] = [
        '#markup' => Markup::create($content),
      ];
    }
    return $build;
  }

}
