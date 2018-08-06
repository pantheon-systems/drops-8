<?php

namespace Drupal\webform_test_element\Element;

use Drupal\Core\Render\Element\Textfield;

/**
 * Provides a webform element for testing webform element plugin.
 *
 * @FormElement("webform_test_element")
 */
class WebformTestElement extends Textfield {

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [];
  }

}
