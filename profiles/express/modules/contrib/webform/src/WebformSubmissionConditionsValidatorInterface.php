<?php

namespace Drupal\webform;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an interface defining a webform conditions (#states) validator.
 */
interface WebformSubmissionConditionsValidatorInterface {

  /**
   * Apply states (aka conditional logic) to wizard pages.
   *
   * @param array $pages
   *   An associative array of webform wizard pages.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return array
   *   An associative array of webform wizard pages with hidden pages removed.
   */
  public function buildPages(array $pages, WebformSubmissionInterface $webform_submission);

  /**
   * Apply form #states to visible elements.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\webform\WebformSubmissionForm::buildForm
   */
  public function buildForm(array &$form, FormStateInterface $form_state);

  /**
   * Validate form #states for visible elements.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\webform\WebformSubmissionForm::validateForm
   */
  public function validateForm(array &$form, FormStateInterface $form_state);

  /**
   * Submit form #states for visible elements.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\webform\WebformSubmissionForm::submitForm
   */
  public function submitForm(array &$form, FormStateInterface $form_state);

  /**
   * Validate state with conditions.
   *
   * @param string $state
   *   A state.
   * @param array $conditions
   *   An associative array containing conditions.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return bool|null
   *   TRUE if conditions validate. NULL if conditions can't be processed.
   */
  public function validateState($state, array $conditions, WebformSubmissionInterface $webform_submission);

  /**
   * Validate #state conditions.
   *
   * @param array $conditions
   *   An associative array containing conditions.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return bool|null
   *   TRUE if the conditions validate. NULL if the conditions can't be
   *   processed. NULL is returned when there is an invalid selector or a
   *   missing element in the conditions.
   *
   * @see drupal_process_states()
   */
  public function validateConditions(array $conditions, WebformSubmissionInterface $webform_submission);

  /**
   * Determine if an element is visible.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return bool
   *   TRUE if the element is visible.
   */
  public function isElementVisible(array $element, WebformSubmissionInterface $webform_submission);

  /**
   * Determine if an element is enabled.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return bool
   *   TRUE if the element is enabled.
   */
  public function isElementEnabled(array $element, WebformSubmissionInterface $webform_submission);

}
