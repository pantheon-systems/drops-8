<?php

namespace Drupal\webform\Element;

/**
 * Provides a webform element for an address element.
 *
 * @FormElement("webform_address")
 */
class WebformAddress extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + ['#theme' => 'webform_composite_address'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
    $elements = [];
    $elements['address'] = [
      '#type' => 'textfield',
      '#title' => t('Address'),
    ];
    $elements['address_2'] = [
      '#type' => 'textfield',
      '#title' => t('Address 2'),
    ];
    $elements['city'] = [
      '#type' => 'textfield',
      '#title' => t('City/Town'),
    ];
    // Any webform options prefixed with 'states_province' will automatically
    // be included within the Composite Element UI.
    // @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase::getCompositeElementOptions
    $elements['state_province'] = [
      '#type' => 'select',
      '#title' => t('State/Province'),
      '#options' => 'state_province_names',
    ];
    $elements['postal_code'] = [
      '#type' => 'textfield',
      '#title' => t('ZIP/Postal Code'),
    ];
    // Any webform options prefixed with 'country' will automatically
    // be included within the Composite Element UI.
    // @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase::getCompositeElementOptions
    $elements['country'] = [
      '#type' => 'select',
      '#title' => t('Country'),
      '#options' => 'country_names',
    ];
    return $elements;
  }

}
