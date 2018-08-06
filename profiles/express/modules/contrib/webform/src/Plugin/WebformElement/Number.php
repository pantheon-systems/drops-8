<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'number' element.
 *
 * @WebformElement(
 *   id = "number",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Number.php/class/Number",
 *   label = @Translation("Number"),
 *   description = @Translation("Provides a form element for numeric input, with special numeric validation."),
 *   category = @Translation("Advanced elements"),
 * )
 */
class Number extends NumericBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      // Number settings.
      'min' => '',
      'max' => '',
      'step' => '',
    ] + parent::getDefaultProperties() + $this->getDefaultMultipleProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return parent::preview() + [
      '#min' => 0,
      '#max' => 10,
      '#step' => 1,
    ];
  }

}
