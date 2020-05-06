<?php

namespace Drupal\webform_test_element\Element;

use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a webform composite element file for testing.
 *
 * @FormElement("webform_test_composite_file")
 */
class WebformTestCompositeFile extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
    $elements = [];
    $elements['textfield'] = [
      '#type' => 'textfield',
      '#title' => t('textfield'),
    ];
    $elements['managed_file'] = [
      '#type' => 'managed_file',
      '#title' => 'managed_file',
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [];
  }

}
