<?php

namespace Drupal\webform\Element;

use Drupal\Core\Render\Element\RenderElement;
use Drupal\webform\Entity\Webform as WebformEntity;
use Drupal\webform\WebformInterface;

/**
 * Provides a render element to display a webform.
 *
 * @RenderElement("webform")
 */
class Webform extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#pre_render' => [
        [$class, 'preRenderWebformElement'],
      ],
      '#webform' => NULL,
      '#default_data' => [],
      '#cache' => ['max-age' => 0],
    ];
  }

  /**
   * Webform element pre render callback.
   */
  public static function preRenderWebformElement($element) {
    $webform = ($element['#webform'] instanceof WebformInterface) ? $element['#webform'] : WebformEntity::load($element['#webform']);
    if (!$webform || !$webform->access('submission_create')) {
      return $element;
    }

    $values = ['data' => $element['#default_data']];
    return $element + $webform->getSubmissionForm($values);
  }

}
