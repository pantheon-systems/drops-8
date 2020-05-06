<?php

namespace Drupal\webform_test_element\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;

/**
 * Provides a 'webform_test_composite_file' element.
 *
 * @WebformElement(
 *   id = "webform_test_composite_file",
 *   label = @Translation("Test composite element file"),
 *   description = @Translation("Provides a Webform composite file element for testing."),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class WebformTestCompositeFile extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [];
  }

}
