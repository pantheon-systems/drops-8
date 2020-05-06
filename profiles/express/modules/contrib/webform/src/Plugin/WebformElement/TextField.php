<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'textfield' element.
 *
 * @WebformElement(
 *   id = "textfield",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Textfield.php/class/Textfield",
 *   label = @Translation("Text field"),
 *   description = @Translation("Provides a form element for input of a single-line text."),
 *   category = @Translation("Basic elements"),
 * )
 */
class TextField extends TextBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      // Form display.
      'input_mask' => '',
      'input_hide' => FALSE,
      // Form validation.
      'counter_type' => '',
      'counter_minimum' => NULL,
      'counter_minimum_message' => '',
      'counter_maximum' => NULL,
      'counter_maximum_message' => '',
    ] + parent::defineDefaultProperties() + $this->defineDefaultMultipleProperties();
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    if (!array_key_exists('#maxlength', $element)) {
      $element['#maxlength'] = 255;
    }
    parent::prepare($element, $webform_submission);
  }

}
