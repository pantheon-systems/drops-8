<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'checkboxes_other' element.
 *
 * @WebformElement(
 *   id = "webform_checkboxes_other",
 *   label = @Translation("Checkboxes other"),
 *   description = @Translation("Provides a form element for a set of checkboxes."),
 *   category = @Translation("Options elements"),
 * )
 */
class WebformCheckboxesOther extends Checkboxes {

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    $element['#element_validate'][] = [get_class($this), 'validateMultipleOptions'];
    parent::prepare($element, $webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorOptions(array $element) {
    $title = $this->getAdminLabel($element);
    $name = $element['#webform_key'];

    $selectors = [];
    foreach ($element['#options'] as $input_name => $input_title) {
      $selectors[":input[name=\"{$name}[checkboxes][{$input_name}]\"]"] = $input_title . ' [' . $this->t('Checkboxes') . ']';
    }
    $selectors[":input[name=\"{$name}[other]\"]"] = $title . ' [' . $this->t('Textfield') . ']';
    return [$title => $selectors];
  }

}
