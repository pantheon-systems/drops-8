<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'radios' element.
 *
 * @WebformElement(
 *   id = "radios",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Radios.php/class/Radios",
 *   label = @Translation("Radios"),
 *   description = @Translation("Provides a form element for a set of radio buttons."),
 *   category = @Translation("Options elements"),
 * )
 */
class Radios extends OptionsBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      // Form display.
      'options_display' => 'one_column',
      'options_description_display' => 'description',
      // iCheck settings.
      'icheck' => '',
    ] + parent::getDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    // Issue #2856795: If radio buttons are required but not filled form is
    // nevertheless submitted.
    // Issue #2856315: Conditional Logic - Requiring Radios in a Fieldset.
    $element['#attached']['library'][] = 'webform/webform.element.radios';
  }

}
