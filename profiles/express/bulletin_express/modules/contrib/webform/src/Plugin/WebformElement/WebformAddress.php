<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormState;
use Drupal\webform\Element\WebformAddress as WebformAddressElement;

/**
 * Provides an 'address' element.
 *
 * @WebformElement(
 *   id = "webform_address",
 *   label = @Translation("Address"),
 *   description = @Translation("Provides a form element to collect address information (street, city, state, zip)."),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class WebformAddress extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  protected function getCompositeElements() {
    return WebformAddressElement::getCompositeElements();
  }

  /**
   * {@inheritdoc}
   */
  protected function getInitializedCompositeElement(array &$element) {
    $form_state = new FormState();
    $form_completed = [];
    return WebformAddressElement::processWebformComposite($element, $form_state, $form_completed);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItemValue(array $element, array $value) {
    return $this->formatTextItemValue($element, $value);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element, array $value) {
    $lines = [];
    if (!empty($value['address'])) {
      $lines['address'] = $value['address'];
    }
    if (!empty($value['address_2'])) {
      $lines['address_2'] = $value['address_2'];
    }
    $location = '';
    if (!empty($value['city'])) {
      $location .= $value['city'];
    }
    if (!empty($value['state_province'])) {
      $location .= ($location) ? ', ' : '';
      $location .= $value['state_province'];
    }
    if (!empty($value['postal_code'])) {
      $location .= ($location) ? '. ' : '';
      $location .= $value['postal_code'];
    }
    if ($location) {
      $lines['location'] = $location;
    }
    if (!empty($value['country'])) {
      $lines['country'] = $value['country'];
    }
    return $lines;
  }

}
