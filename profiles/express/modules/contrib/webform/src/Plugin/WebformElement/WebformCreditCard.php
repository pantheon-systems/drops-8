<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'creditcard' element.
 *
 * @WebformElement(
 *   id = "webform_creditcard",
 *   label = @Translation("Credit card"),
 *   description = @Translation("Provides a form element to collect credit card information (card holder name, card number, cv, card expiration data)."),
 *   category = @Translation("Composite elements"),
 *   hidden = TRUE,
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class WebformCreditCard extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = parent::getDefaultProperties();
    unset(
      $properties['multiple'],
      $properties['expiration_month__options'],
      $properties['expiration_year__options']
    );
    return $properties;
  }

}
