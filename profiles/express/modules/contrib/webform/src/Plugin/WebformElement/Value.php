<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\WebformInterface;

/**
 * Provides a 'value' element.
 *
 * @WebformElement(
 *   id = "value",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Value.php/class/Value",
 *   label = @Translation("Value"),
 *   description = @Translation("Provides a form element for storage of internal information."),
 *   category = @Translation("Advanced elements"),
 * )
 */
class Value extends TextBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      // Element settings.
      'title' => '',
      'value' => '',
    ];
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [
      '#type' => 'value',
      '#title' => $this->t('Value'),
      '#value' => 'preview',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    // Value elements should never get a test value.
    return NULL;
  }

}
