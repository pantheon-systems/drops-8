<?php

namespace Drupal\webform\Element;

/**
 * Provides a webform element for an name element.
 *
 * @FormElement("webform_name")
 */
class WebformName extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + ['#theme' => 'webform_composite_name'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
    $elements = [];
    // Any webform options prefixed with 'title' will automatically
    // be included within the Composite Element UI.
    // @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase::getCompositeElementOptions
    $elements['title'] = [
      '#type' => 'webform_select_other',
      '#title' => t('Title'),
      '#options' => 'titles',
    ];
    $elements['first'] = [
      '#type' => 'textfield',
      '#title' => t('First'),
    ];
    $elements['middle'] = [
      '#type' => 'textfield',
      '#title' => t('Middle'),
    ];
    $elements['last'] = [
      '#type' => 'textfield',
      '#title' => t('Last'),
    ];
    $elements['suffix'] = [
      '#type' => 'textfield',
      '#title' => t('Suffix'),
    ];
    $elements['degree'] = [
      '#type' => 'textfield',
      '#title' => t('Degree'),
    ];
    return $elements;
  }

}
