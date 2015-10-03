<?php

/**
 * @file
 * Contains \Drupal\render_attached_test\Controller\TestController.
 */

namespace Drupal\render_attached_test\Controller;

/**
 * Controller for various permutations of #attached in the render array.
 */
class TestController {

  /**
   * Test special header and status code rendering.
   *
   * @return array
   *   A render array using features of the 'http_header' directive.
   */
  public function teapotHeaderStatus() {
    $render = [];
    $render['#attached']['http_header'][] = ['Status', "418 I'm a teapot."];
    return $render;
  }

  /**
   * Test attached HTML head rendering.
   *
   * @return array
   *   A render array using the 'http_head' directive.
   */
  public function header() {
    $render = [];
    $render['#attached']['http_header'][] = ['X-Test-Teapot-Replace', 'This value gets replaced'];
    $render['#attached']['http_header'][] = ['X-Test-Teapot-Replace', 'Teapot replaced', TRUE];
    $render['#attached']['http_header'][] = ['X-Test-Teapot-No-Replace', 'This value is not replaced'];
    $render['#attached']['http_header'][] = ['X-Test-Teapot-No-Replace', 'This one is added', FALSE];
    $render['#attached']['http_header'][] = ['X-Test-Teapot', 'Teapot Mode Active'];
    return $render;
  }

  /**
   * Test attached HTML head rendering.
   *
   * @return array
   *   A render array using the 'html_head' directive.
   */
  public function head() {
    $head = [
      [
        '#tag' => 'meta',
        '#attributes' => [
          'test-attribute' => 'testvalue',
        ],
      ],
      'test_head_attribute',
    ];

    $render = [];
    $render['#attached']['html_head'][] = $head;
    return $render;
  }

  /**
   * Test attached feed rendering.
   *
   * @return array
   *   A render array using the 'feed' directive.
   */
  public function feed() {
    $render = [];
    $render['#attached']['feed'][] = ['test://url', 'Your RSS feed.'];
    return $render;
  }

  /**
   * Test special header and status code rendering as a side-effect.
   *
   * @return array
   *   A generic render array.
   */
  public function teapotHeaderStatusDpa() {
    drupal_process_attached($this->teapotHeaderStatus());
    return ['#markup' => "I'm some markup here to fool the kernel into rendering this page."];
  }

  /**
   * Test attached HTML head rendering as a side-effect.
   *
   * @return array
   *   A render array using the 'http_header' directive.
   */
  public function headerDpa() {
    drupal_process_attached($this->header());
    return ['#markup' => "I'm some markup here to fool the kernel into rendering this page."];
  }

  /**
   * Test attached HTML head rendering as a side-effect.
   *
   * @return array
   *   A render array using the 'html_head' directive.
   */
  public function headDpa() {
    drupal_process_attached($this->head());
    return ['#markup' => "I'm some markup here to fool the kernel into rendering this page."];
  }

  /**
   * Test attached feed rendering as a side-effect.
   *
   * @return array
   *   A render array using the 'feed' directive.
   */
  public function feedDpa() {
    drupal_process_attached($this->feed());
    return ['#markup' => "I'm some markup here to fool the kernel into rendering this page."];
  }

}
