<?php

namespace Drupal\libraries_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Component\Serialization\Yaml;


/**
 * Class LibraresUiController.
 *
 * @package Drupal\libraries_ui\Controller
 */
class LibraresUiController extends ControllerBase {

  /**
   * Libraries UI.
   *
   * @return string
   *   Information Libraries UI info.
   */
  public function librariesui() {
    $class = get_class($this);
    return [
        '#theme' => 'libraries_ui',
        '#pre_render' => [
            [$class, 'preRenderMyElement'],
        ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderMyElement($element) {
      $librariesInfo = \Drupal::service('libraries_ui.default')->getAllLibraries();
      $element['libraries_ui'] = $librariesInfo;
    return $element;
  }

}
