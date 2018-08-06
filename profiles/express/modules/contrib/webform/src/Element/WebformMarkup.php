<?php

namespace Drupal\webform\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element for webform markup.
 *
 * @FormElement("webform_markup")
 */
class WebformMarkup extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [];
  }

}
