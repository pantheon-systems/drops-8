<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElementOtherInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformSubmissionConditionsValidator;

/**
 * Provides a 'checkboxes_other' element.
 *
 * @WebformElement(
 *   id = "webform_checkboxes_other",
 *   label = @Translation("Checkboxes other"),
 *   description = @Translation("Provides a form element for a set of checkboxes, with the ability to enter a custom value."),
 *   category = @Translation("Options elements"),
 * )
 */
class WebformCheckboxesOther extends Checkboxes implements WebformElementOtherInterface {

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

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorInputValue($selector, $trigger, array $element, WebformSubmissionInterface $webform_submission) {
    $input_name = WebformSubmissionConditionsValidator::getSelectorInputName($selector);
    $other_type = WebformSubmissionConditionsValidator::getInputNameAsArray($input_name, 1);
    $value = $this->getRawValue($element, $webform_submission) ?: [];
    if ($other_type === 'other') {
      $other_value = array_diff($value, array_keys($element['#options']));
      return ($other_value) ? implode(', ', $other_value) : NULL;
    }
    else {
      $option_value = WebformSubmissionConditionsValidator::getInputNameAsArray($input_name, 2);
      if (in_array($option_value, $value, TRUE)) {
        return (in_array($trigger, ['checked', 'unchecked'])) ? TRUE : $value;
      }
      else {
        return (in_array($trigger, ['checked', 'unchecked'])) ? FALSE : NULL;
      }
    }
  }

}
