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
  protected function defineDefaultProperties() {
    return [
      'wrapper_type' => 'fieldset',
    ] + parent::defineDefaultProperties();
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);
    $element['#theme_wrappers'] = [];
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareElementValidateCallbacks(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepareElementValidateCallbacks($element, $webform_submission);
    $element['#element_validate'][] = [get_class($this), 'validatePasswordConfirm'];
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareElementPreRenderCallbacks(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    $element['#pre_render'] = [[get_called_class(), 'preRenderWebformCompositeFormElement']];
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
    $value = $element['#value'];
    $form_state->setValueForElement($element, $value['pass1']);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Remove unsupported title and description display from composite elements.
    if ($this->isComposite()) {
      unset($form['form']['display_container']['title_display']['#options']['inline']);
      unset($form['form']['display_container']['description_display']['#options']['tooltip']);
    }

    return $form;
  }

}
