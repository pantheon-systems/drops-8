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
  public static function getCompositeElements() {
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
    $elements['state_province'] = [
      '#type' => 'select',
      '#title' => t('State/Province'),
      '#options' => 'state_province_names',
    ];
    $elements['postal_code'] = [
      '#type' => 'textfield',
      '#title' => t('Zip/Postal Code'),
    ];
    $elements['country'] = [
      '#type' => 'select',
      '#title' => t('Country'),
      '#options' => 'country_names',
    ];
    return $elements;
  }

}
