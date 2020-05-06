<?php

namespace Drupal\webform_test_element\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'webform_test_element' element.
 *
 * @WebformElement(
 *   id = "webform_test_element",
 *   label = @Translation("Test element"),
 *   description = @Translation("Provides a form element for testing.")
 * )
 */
class WebformTestElement extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    $this->displayMessage(__FUNCTION__);
    $element['#element_validate'][] = [get_class($this), 'validate'];
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValue(array &$element) {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function formatHtml(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $this->displayMessage(__FUNCTION__);
    return '<i>' . $this->formatText($element, $webform_submission, $options) . '</i>';
  }

  /**
   * {@inheritdoc}
   */
  public function formatText(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    $this->displayMessage(__FUNCTION__);
    return strtoupper($value);
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function preCreate(array &$element, array &$values) {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(array &$element, WebformSubmissionInterface $webform_submission) {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function postLoad(array &$element, WebformSubmissionInterface $webform_submission) {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function preDelete(array &$element, WebformSubmissionInterface $webform_submission) {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function postDelete(array &$element, WebformSubmissionInterface $webform_submission) {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(array &$element, WebformSubmissionInterface $webform_submission) {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array &$element, WebformSubmissionInterface $webform_submission) {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(array &$element, WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $this->displayMessage(__FUNCTION__, $update ? 'update' : 'insert');
  }

  /**
   * Display the invoked plugin method to end user.
   *
   * @param string $method_name
   *   The invoked method name.
   * @param string $context1
   *   Additional parameter passed to the invoked method name.
   */
  protected function displayMessage($method_name, $context1 = NULL) {
    if (PHP_SAPI != 'cli') {
      $t_args = ['@class_name' => get_class($this), '@method_name' => $method_name, '@context1' => $context1];
      $this->messenger()->addStatus($this->t('Invoked: @class_name:@method_name @context1', $t_args));
    }
  }

  /**
   * Form API callback. Convert password confirm array to single value.
   */
  public static function validate(array &$element, FormStateInterface $form_state) {
    \Drupal::messenger()->addStatus(t('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement::validate'));
  }

}
