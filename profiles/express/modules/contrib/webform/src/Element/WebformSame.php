<?php

namespace Drupal\webform\Element;

use Drupal\Core\Render\Element\Checkbox;

/**
 * Provides a webform element for managing same as.
 *
 * @FormElement("webform_same")
 *
 * @see \Drupal\webform\Plugin\WebformElement\WebformSame
 */
class WebformSame extends Checkbox {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + [
      '#source' => NULL,
      '#destination' => NULL,
      '#destination_state' => 'visible',
    ];
  }

}
