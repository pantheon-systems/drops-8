<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'value' element.
 *
 * @WebformElement(
 *   id = "value",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Value.php/class/Value",
 *   label = @Translation("Value"),
 *   description = @Translation("Provides a form element for storage of internal information."),
 *   category = @Translation("Advanced elements"),
 * )
 */
class Value extends TextBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      // Element settings.
      'value' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorOptions(array $element) {
    return [];
  }

}
