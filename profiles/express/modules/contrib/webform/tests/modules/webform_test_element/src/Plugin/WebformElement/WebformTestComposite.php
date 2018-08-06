<?php

namespace Drupal\webform_test_element\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;

/**
 * Provides a 'webform_test_composite' element.
 *
 * @WebformElement(
 *   id = "webform_test_composite",
 *   label = @Translation("Test composite element"),
 *   description = @Translation("Provides a Webform composite element for testing."),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class WebformTestComposite extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [];
  }

}
