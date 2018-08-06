<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionConditionsValidator;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'checkboxes' element.
 *
 * @WebformElement(
 *   id = "checkboxes",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Checkboxes.php/class/Checkboxes",
 *   label = @Translation("Checkboxes"),
 *   description = @Translation("Provides a form element for a set of checkboxes, with the ability to enter a custom value."),
 *   category = @Translation("Options elements"),
 * )
 */
class Checkboxes extends OptionsBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      'multiple' => TRUE,
      'multiple_error' => '',
      // Options settings.
      'options_display' => 'one_column',
      'options_description_display' => 'description',
      // iCheck settings.
      'icheck' => '',
    ] + parent::getDefaultProperties();
  }

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
    $element['#element_validate'][] = [get_class($this), 'validateMultipleOptions'];
    parent::prepare($element, $webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  protected function getElementSelectorInputsOptions(array $element) {
    $selectors = $element['#options'];
    foreach ($selectors as &$text) {
      $text .= ' [' . $this->t('Checkbox') . ']';
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
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Checkboxes must require > 2 options.
    $form['element']['multiple']['#min'] = 2;

    return $form;
  }

}
