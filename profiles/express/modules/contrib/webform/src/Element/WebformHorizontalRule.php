<?php

namespace Drupal\webform\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element for horizontal rule.
 *
 * @FormElement("webform_horizontal_rule")
 */
class WebformHorizontalRule extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#theme' => 'webform_horizontal_rule',
    ];
  }

}
