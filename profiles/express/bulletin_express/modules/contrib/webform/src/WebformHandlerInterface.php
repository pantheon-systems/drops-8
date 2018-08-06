<?php

namespace Drupal\webform;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the interface for webform handlers.
 *
 * @see \Drupal\webform\Annotation\WebformHandler
 * @see \Drupal\webform\WebformHandlerBase
 * @see \Drupal\webform\WebformHandlerManager
 * @see \Drupal\webform\WebformHandlerManagerInterface
 * @see plugin_api
 */
interface WebformHandlerInterface extends PluginInspectionInterface, ConfigurablePluginInterface, ContainerFactoryPluginInterface, PluginFormInterface {

  /**
   * Value indicating unlimited plugin instances are permitted.
   */
  const CARDINALITY_UNLIMITED = -1;

  /**
   * Value indicating a single plugin instances are permitted.
   */
  const CARDINALITY_SINGLE = 1;

  /**
   * Value indicating webform submissions are not processed (ie email or saved) by the handler.
   */
  const RESULTS_IGNORED = 0;

  /**
   * Value indicating webform submissions must be stored in the database.
   */
  const SUBMISSION_REQUIRED = 1;

  /**
   * Value indicating webform submissions do not have to be stored in the database.
   */
  const SUBMISSION_OPTIONAL = 0;

  /**
   * Value indicating webform submissions are processed (ie email or saved) by the handler.
   */
  const RESULTS_PROCESSED = 1;

  /**
   * Returns a render array summarizing the configuration of the webform handler.
   *
   * @return array
   *   A render array.
   */
  public function getSummary();

  /**
   * Returns the webform handler label.
   *
   * @return string
   *   The webform handler label.
   */
  public function label();

  /**
   * Returns the webform handler description.
   *
   * @return string
   *   The webform handler description.
   */
  public function description();

  /**
   * Returns the webform handler cardinality settings.
   *
   * @return string
   *   The webform handler cardinality settings.
   */
  public function cardinality();

  /**
   * Returns the unique ID representing the webform handler.
   *
   * @return string
   *   The webform handler ID.
   */
  public function getHandlerId();

  /**
   * Sets the id for this webform handler.
   *
   * @param int $handler_id
   *   The handler_id for this webform handler.
   *
   * @return $this
   */
  public function setHandlerId($handler_id);

  /**
   * Returns the label of the webform handler.
   *
   * @return int|string
   *   Either the integer label of the webform handler, or an empty string.
   */
  public function getLabel();

  /**
   * Sets the label for this webform handler.
   *
   * @param int $label
   *   The label for this webform handler.
   *
   * @return $this
   */
  public function setLabel($label);

  /**
   * Returns the weight of the webform handler.
   *
   * @return int|string
   *   Either the integer weight of the webform handler, or an empty string.
   */
  public function getWeight();

  /**
   * Sets the weight for this webform handler.
   *
   * @param int $weight
   *   The weight for this webform handler.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Returns the status of the webform handler.
   *
   * @return bool
   *   The status of the webform handler.
   */
  public function getStatus();

  /**
   * Sets the status for this webform handler.
   *
   * @param bool $status
   *   The status for this webform handler.
   *
   * @return $this
   */
  public function setStatus($status);

  /**
   * Returns the webform handler enabled indicator.
   *
   * @return bool
   *   TRUE if the webform handler is enabled.
   */
  public function isEnabled();

  /**
   * Returns the webform handler disabled indicator.
   *
   * @return bool
   *   TRUE if the webform handler is disabled.
   */
  public function isDisabled();

  /**
   * Returns the webform submission is optional indicator.
   *
   * @return bool
   *   TRUE if the webform handler does not require the webform submission to
   *   be saved to the database.
   */
  public function isSubmissionOptional();

  /**
   * Returns the webform submission is required indicator.
   *
   * @return bool
   *   TRUE if the webform handler requires the webform submission to be saved
   *   to the database.
   */
  public function isSubmissionRequired();

  /**
   * Initialize webform handler.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform object.
   *
   * @return $this
   *   This webform handler.
   */
  public function setWebform(WebformInterface $webform);

  /**
   * Get the webform that this handler is attached to.
   *
   * @return \Drupal\webform\WebformInterface
   *   A webform.
   */
  public function getWebform();

  /**
   * Alter webform submission webform elements.
   *
   * @param array $elements
   *   An associative array containing the webform elements.
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   */
  public function alterElements(array &$elements, WebformInterface $webform);

  /**
   * Alter webform submission webform .
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   */
  public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission);

  /**
   * Validate webform submission webform .
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission);

  /**
   * Submit webform submission webform.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission);

  /**
   * Confirm webform submission webform.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   */
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission);

  /**
   * Changes the values of an entity before it is created.
   *
   * @param mixed[] $values
   *   An array of values to set, keyed by property name.
   */
  public function preCreate(array $values);

  /**
   * Acts on a webform submission after it is created.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   */
  public function postCreate(WebformSubmissionInterface $webform_submission);

  /**
   * Acts on loaded webform submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   */
  public function postLoad(WebformSubmissionInterface $webform_submission);

  /**
   * Acts on a webform submission before the presave hook is invoked.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   */
  public function preSave(WebformSubmissionInterface $webform_submission);

  /**
   * Acts on a saved webform submission before the insert or update hook is invoked.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param bool $update
   *   TRUE if the entity has been updated, or FALSE if it has been inserted.
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE);

  /**
   * Acts on a webform submission before they are deleted and before hooks are invoked.
   *
   * Used before the entities are deleted and before invoking the delete hook.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   */
  public function preDelete(WebformSubmissionInterface $webform_submission);

  /**
   * Acts on deleted a webform submission before the delete hook is invoked.
   *
   * Used after the entities are deleted but before invoking the delete hook.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   */
  public function postDelete(WebformSubmissionInterface $webform_submission);

  /**
   * Acts on handler after it has been created and added to webform.
   */
  public function createHandler();

  /**
   * Acts on handler after it has been updated.
   */
  public function updateHandler();

  /**
   * Acts on handler after it has been removed.
   */
  public function deleteHandler();

}
