<?php

namespace Drupal\webform\Element;

/**
 * Provides a webform element for a credit card element.
 *
 * @FormElement("webform_creditcard")
 */
class WebformCreditCard extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + ['#theme' => 'webform_composite_creditcard'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements() {
    $month_options = range(1, 12);
    $year_options = range(date('Y'), date('Y') + 10);

    $elements = [];
    $elements['warning'] = [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => t('The credit card element is experimental and insecure because it stores submitted information as plain text.'),
    ];
    $elements['name'] = [
      '#type' => 'textfield',
      '#title' => t("Name on Card"),
    ];
    $elements['type'] = [
      '#type' => 'select',
      '#title' => t('Type of Card'),
      '#options' => 'creditcard_codes',
      '#empty_option' => '',
    ];
    $elements['number'] = [
      '#type' => 'webform_creditcard_number',
      '#title' => t('Card Number'),
    ];
    $elements['civ'] = [
      '#type' => 'number',
      '#title' => t('CIV Number'),
      '#min' => 1,
      '#size' => 4,
      '#maxlength' => 4,
      '#test' => [111, 222, 333],
    ];
    $elements['expiration'] = [
      '#type' => 'label',
      '#title' => t('Expiration Date'),
    ];
    $elements['expiration_month'] = [
      '#title' => t('Expiration Month'),
      '#title_display' => 'invisible',
      '#type' => 'select',
      '#options' => array_combine($month_options, $month_options),
      '#prefix' => '<div class="container-inline clearfix">',
    ];
    $elements['expiration_year'] = [
      '#title' => t('Expiration Year'),
      '#title_display' => 'invisible',
      '#type' => 'select',
      '#options' => array_combine($year_options, $year_options),
      '#suffix' => '</div>',
    ];

    return $elements;
  }

}
