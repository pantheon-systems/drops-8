<?php

namespace Drupal\webform\Element;

/**
 * Provides a webform element for a contact element.
 *
 * @FormElement("webform_contact")
 */
class WebformContact extends WebformAddress {

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements() {
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
    $elements += parent::getCompositeElements();
    return $elements;
  }

}
