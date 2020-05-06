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
  public function getInfo() {
    return parent::getInfo() + ['#theme' => 'webform_composite_link'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
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
