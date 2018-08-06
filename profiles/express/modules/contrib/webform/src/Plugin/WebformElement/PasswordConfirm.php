<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'password_confirm' element.
 *
 * @WebformElement(
 *   id = "password_confirm",
 *   label = @Translation("Password confirm"),
 *   category = @Translation("Advanced elements"),
 *   description = @Translation("Provides a form element for double-input of passwords."),
 *   states_wrapper = TRUE,
 * )
 */
class PasswordConfirm extends Password {

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);
    $element['#element_validate'][] = [get_class($this), 'validatePasswordConfirm'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  protected function getElementSelectorInputsOptions(array $element) {
    return [
      'pass1' => $this->getAdminLabel($element) . ' 1 [' . $this->t('Password') . ']',
      'pass2' => $this->getAdminLabel($element) . ' 2 [' . $this->t('Password') . ']',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValue(array &$element) {
    if (isset($element['#default_value'])) {
      $element['#default_value'] = [
        'pass1' => $element['#default_value'],
        'pass2' => $element['#default_value'],
      ];
    }
  }

  /**
   * Form API callback. Convert password confirm array to single value.
   */
  public static function validatePasswordConfirm(array &$element, FormStateInterface $form_state, array &$completed_form) {
    $name = $element['#name'];
    $value = $form_state->getValue($name);
    $form_state->setValue($name, $value['pass1']);
  }

}
