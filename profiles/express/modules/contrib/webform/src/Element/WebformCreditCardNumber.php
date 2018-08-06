<?php

namespace Drupal\webform\Element;

use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a webform element for entering a credit card number.
 *
 * @FormElement("webform_creditcard_number")
 */
class WebformCreditCardNumber extends FormElement {

  /**
   * Defines the max length for an credit card number.
   */
  const CREDITCARD_MAX_LENGTH = 16;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#size' => self::CREDITCARD_MAX_LENGTH,
      '#maxlength' => self::CREDITCARD_MAX_LENGTH,
      '#autocomplete_route_name' => FALSE,
      '#process' => [
        [$class, 'processAutocomplete'],
        [$class, 'processAjaxForm'],
        [$class, 'processPattern'],
      ],
      '#element_validate' => [
        [$class, 'validateWebformCreditCardNumber'],
      ],
      '#pre_render' => [
        [$class, 'preRenderWebformCreditCardNumber'],
      ],
      '#theme' => 'input__webform_creditcard_number',
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Webform element validation handler for #type 'creditcard_number'.
   */
  public static function validateWebformCreditCardNumber(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = trim($element['#value']);
    $form_state->setValueForElement($element, $value);

    if ($value !== '' && !static::validCreditCardNumber($value)) {
      $form_state->setError($element, t('The credit card number is not valid.'));
    }
  }

  /**
   * Validation rule for credit card number.
   *
   * Luhn algorithm number checker - (c) 2005-2008 shaman - www.planzero.org
   * This code has been released into the public domain, however please
   * give credit to the original author where possible.
   *
   * @param string $number
   *   A credit card number.
   *
   * @return bool
   *   TRUE is credit card number is valid.
   *
   * @see: http://stackoverflow.com/questions/174730/what-is-the-best-way-to-validate-a-credit-card-in-php
   */
  public static function validCreditCardNumber($number) {
    // If number is not 15 or 16 digits return FALSE.
    if (!preg_match('/^\d{15,16}$/', $number)) {
      return FALSE;
    }

    // Set the string length and parity.
    $number_length = strlen($number);
    $parity = $number_length % 2;

    // Loop through each digit and do the maths.
    $total = 0;
    for ($i = 0; $i < $number_length; $i++) {
      $digit = $number[$i];
      // Multiply alternate digits by two.
      if ($i % 2 == $parity) {
        $digit *= 2;
        // If the sum is two digits, add them together (in effect).
        if ($digit > 9) {
          $digit -= 9;
        }
      }
      // Total up the digits.
      $total += $digit;
    }

    // If the total mod 10 equals 0, the number is valid.
    return ($total % 10 == 0) ? TRUE : FALSE;
  }

  /**
   * Prepares a #type 'creditcard_number' render element for theme_element().
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #size, #maxlength,
   *   #placeholder, #required, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for theme_element().
   */
  public static function preRenderWebformCreditCardNumber(array $element) {
    $element['#attributes']['type'] = 'text';
    Element::setAttributes($element, ['id', 'name', 'value', 'size', 'maxlength', 'placeholder']);
    static::setAttributes($element, ['form-text', 'webform-creditcard-number']);
    return $element;
  }

}
