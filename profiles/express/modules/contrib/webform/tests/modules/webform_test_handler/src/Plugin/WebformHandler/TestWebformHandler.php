<?php

namespace Drupal\webform_test_handler\Plugin\WebformHandler;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Webform submission test handler.
 *
 * @WebformHandler(
 *   id = "test",
 *   label = @Translation("Test"),
 *   category = @Translation("Testing"),
 *   description = @Translation("Tests webform submission handler behaviors."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class TestWebformHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'message' => 'One two one two this is just a test',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message'),
      '#default_value' => $this->configuration['message'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['message'] = $form_state->getValue('message');
  }

  /**
   * {@inheritdoc}
   */
  public function alterElements(array &$elements, WebformInterface $webform) {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function alterElement(array &$element, FormStateInterface $form_state, array $context) {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function overrideSettings(array &$settings, WebformSubmissionInterface $webform_submission) {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $this->displayMessage(__FUNCTION__);
    $value = $form_state->getValue('element');
    if ($value && !in_array($value, ['access_allowed', 'submission_access_denied', 'element_access_denied'])) {
      $form_state->setErrorByName('element', $this->t('The element must be empty. You entered %value.', ['%value' => $value]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $this->messenger()->addStatus($this->configuration['message'], TRUE);
    \Drupal::logger('webform.test_form')->notice($this->configuration['message']);
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function preCreate(array &$values) {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(WebformSubmissionInterface $webform_submission) {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function postLoad(WebformSubmissionInterface $webform_submission) {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function preDelete(WebformSubmissionInterface $webform_submission) {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function postDelete(WebformSubmissionInterface $webform_submission) {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(WebformSubmissionInterface $webform_submission) {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $this->displayMessage(__FUNCTION__, $update ? 'update' : 'insert');
  }

  /**
   * {@inheritdoc}
   */
  public function access(WebformSubmissionInterface $webform_submission, $operation, AccountInterface $account = NULL) {
    $this->displayMessage(__FUNCTION__ . 'Submission');
    $value = $webform_submission->getElementData('element');
    if ($value === 'submission_access_denied') {
      $access_result = AccessResult::forbidden();
    }
    else {
      $access_result = parent::access($webform_submission, $operation, $account);
    }
    return $access_result->setCacheMaxAge(0);
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessConfirmation(array &$variables) {
    $this->displayMessage(__FUNCTION__);
    $variables['message'] = '::preprocessConfirmation';
  }

  /**
   * {@inheritdoc}
   */
  public function createHandler() {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function updateHandler() {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteHandler() {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function accessElement(array &$element, $operation, AccountInterface $account = NULL) {
    $this->displayMessage(__FUNCTION__);

    $webform_submission = $this->getWebformSubmission();
    if ($webform_submission
      && $webform_submission->getElementData('element') === 'element_access_denied') {
      $access_result = AccessResult::forbidden();
    }
    else {
      $access_result = parent::accessElement($element, $operation, $account);
    }

    return $access_result->setCacheMaxAge(0);
  }

  /**
   * {@inheritdoc}
   */
  public function createElement($key, array $element) {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function updateElement($key, array $element, array $original_element) {
    $this->displayMessage(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteElement($key, array $element) {
    $this->displayMessage(__FUNCTION__);
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
      $t_args = [
        '@id' => $this->getHandlerId(),
        '@class_name' => get_class($this),
        '@method_name' => $method_name,
        '@context1' => $context1,
      ];
      $this->messenger()->addStatus($this->t('Invoked @id: @class_name:@method_name @context1', $t_args), TRUE);
      \Drupal::logger('webform.test_form')->notice('Invoked: @class_name:@method_name @context1', $t_args);
    }
  }

}
