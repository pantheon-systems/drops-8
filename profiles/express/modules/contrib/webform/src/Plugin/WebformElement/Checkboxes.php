<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Utility\WebformOptionsHelper;
use Drupal\webform\WebformSubmissionConditionsValidator;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'checkboxes' element.
 *
 * @WebformElement(
 *   id = "checkboxes",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Checkboxes.php/class/Checkboxes",
 *   label = @Translation("Checkboxes"),
 *   description = @Translation("Provides a form element for a set of checkboxes."),
 *   category = @Translation("Options elements"),
 * )
 */
class Checkboxes extends OptionsBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      'multiple' => TRUE,
      'multiple_error' => '',
      // Options settings.
      'options_display' => 'one_column',
      'options_description_display' => 'description',
      'options__properties' => [],
      // Wrapper.
      'wrapper_type' => 'fieldset',
    ] + parent::defineDefaultProperties();
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function supportsMultipleValues() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasMultipleValues(array $element) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    // Issue #3068998: Checkboxes validation UI is different than
    // other elements.
    $element['#attached']['library'][] = 'webform/webform.element.checkboxes';
  }

  /**
   * {@inheritdoc}
   */
  protected function getElementSelectorInputsOptions(array $element) {
    $selectors = $element['#options'];
    foreach ($selectors as $index => $text) {
      // Remove description from text.
      list($text) = explode(WebformOptionsHelper::DESCRIPTION_DELIMITER, $text);
      // Append element type to text.
      $text .= ' [' . $this->t('Checkbox') . ']';
      $selectors[$index] = $text;
    }
    return $selectors;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorInputValue($selector, $trigger, array $element, WebformSubmissionInterface $webform_submission) {
    $input_name = WebformSubmissionConditionsValidator::getSelectorInputName($selector);
    $option_value = WebformSubmissionConditionsValidator::getInputNameAsArray($input_name, 1);
    $value = $this->getRawValue($element, $webform_submission) ?: [];
    if (in_array($option_value, $value, TRUE)) {
      return (in_array($trigger, ['checked', 'unchecked'])) ? TRUE : $value;
    }
    else {
      return (in_array($trigger, ['checked', 'unchecked'])) ? FALSE : NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorSourceValues(array $element) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Checkboxes must require > 2 options.
    $form['element']['multiple']['#min'] = 2;

    return $form;
  }

}
