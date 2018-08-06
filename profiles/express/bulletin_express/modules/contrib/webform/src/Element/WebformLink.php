<?php

namespace Drupal\webform\Element;

/**
 * Provides a webform element for a link.
 *
 * @FormElement("webform_link")
 */
class WebformLink extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements() {
    $elements = [];
    $elements['title'] = [
      '#type' => 'textfield',
      '#title' => t('Link Title'),
      '#maxlength' => 255,
    ];
    $elements['url'] = [
      '#type' => 'url',
      '#title' => t('Link URL'),
    ];
    return $elements;
  }

}
