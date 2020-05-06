<?php

namespace Drupal\webform\Element;

/**
 * Provides a webform element for a contact element.
 *
 * @FormElement("webform_contact")
 */
class WebformContact extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + ['#theme' => 'webform_composite_contact'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
    $elements = [];
    $elements['name'] = [
      '#type' => 'textfield',
      '#title' => t('Name'),
    ];
    $elements['company'] = [
      '#type' => 'textfield',
      '#title' => t('Company'),
    ];
    $elements['email'] = [
      '#type' => 'email',
      '#title' => t('Email'),
    ];
    $elements['phone'] = [
      '#type' => 'tel',
      '#title' => t('Phone'),
    ];
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
      '#title' => t('ZIP/Postal Code'),
    ];
    $elements['country'] = [
      '#type' => 'select',
      '#title' => t('Country'),
      '#options' => 'country_names',
    ];
    return $elements;
  }

}
