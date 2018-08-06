<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormState;
use Drupal\webform\Element\WebformContact as WebformContactElement;

/**
 * Provides a 'contact' element.
 *
 * @WebformElement(
 *   id = "webform_contact",
 *   label = @Translation("Contact"),
 *   description = @Translation("Provides a form element to collect contact information (name, address, phone, email)."),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class WebformContact extends WebformAddress {

  /**
   * {@inheritdoc}
   */
  protected function getCompositeElements() {
    return WebformContactElement::getCompositeElements();
  }

  /**
   * {@inheritdoc}
   */
  protected function getInitializedCompositeElement(array &$element) {
    $form_state = new FormState();
    $form_completed = [];
    return WebformContactElement::processWebformComposite($element, $form_state, $form_completed);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element, array $value) {
    $lines = [];
    if (!empty($value['name'])) {
      $lines['name'] = $value['name'];
    }
    if (!empty($value['company'])) {
      $lines['company'] = $value['company'];
    }
    $lines += parent::formatTextItemValue($element, $value);
    if (!empty($value['email'])) {
      $lines['email'] = $value['email'];
    }
    if (!empty($value['phone'])) {
      $lines['phone'] = $value['phone'];
    }
    return $lines;
  }

}
