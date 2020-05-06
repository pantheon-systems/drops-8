<?php

namespace Drupal\webform\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element for help (tooltip).
 *
 * @FormElement("webform_help")
 */
class WebformHelp extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#help' => '',
      '#help_title' => '',
      '#theme' => 'webform_element_help',
      '#attributes' => [],
    ];
  }

}
