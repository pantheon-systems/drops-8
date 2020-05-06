<?php

namespace Drupal\webform\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Defines the interface for webform handlers.
 *
 * @see \Drupal\webform\Annotation\WebformHandler
 * @see \Drupal\webform\Plugin\WebformHandlerBase
 * @see \Drupal\webform\Plugin\WebformHandlerManager
 * @see \Drupal\webform\Plugin\WebformHandlerManagerInterface
 * @see plugin_api
 */
interface WebformHandlerInterface extends PluginInspectionInterface, ConfigurableInterface, ContainerFactoryPluginInterface, PluginFormInterface {

  /**
   * Value indicating unlimited plugin instances are permitted.
   */
  const CARDINALITY_UNLIMITED = -1;

  /**
   * Value indicating a single plugin instances are permitted.
   */
  const CARDINALITY_SINGLE = 1;

  /**
   * Value indicating webform submissions are not processed (i.e. email or saved) by the handler.
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
   * Value indicating webform submissions are processed (i.e. email or saved) by the handler.
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
   * Determine if webform handler supports conditions.
   *
   * @return bool
   *   TRUE if the webform handler supports conditions.
   */
  public function supportsConditions();

  /**
   * Determine if webform handler supports tokens.
   *
   * @return bool
   *   TRUE if the webform handler supports tokens.
   */
  public function supportsTokens();

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
   * Returns the conditions the webform handler.
   *
   * @return array
   *   The conditions of the webform handler.
   */
  public function getConditions();

  /**
   * Sets the conditions for this webform handler.
   *
   * @param array $conditions
   *   The conditional logic for this webform handler.
   *
   * @return $this
   */
  public function setConditions(array $conditions);

  /**
   * Enables the webform handler.
   *
   * @return $this
   */
  public function enable();

  /**
   * Disables the webform handler.
   *
   * @return $this
   */
  public function disable();

  /**
   * Checks if the handler is excluded via webform.settings.
   *
   * @return bool
   *   TRUE if the handler is excluded.
   */
  public function isExcluded();

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
   * Determine if this handle is applicable to the webform.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return bool
   *   TRUE if this handler is applicable to the webform.
   */
  public function isApplicable(WebformInterface $webform);

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
   * Determine if the webform handler requires anonymous submission tracking.
   *
   * @return bool
   *   TRUE if the webform handler requires anonymous submission tracking.
   *
   * @see \Drupal\webform_options_limit\Plugin\WebformHandler\OptionsLimitWebformHandler
   */
  public function hasAnonymousSubmissionTracking();

  /**
   * Set the webform that this is handler is attached to.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return $this
   *   This webform handler.
   *
   * @todo Webform 8.x-6.x: Replace with WebformEntityInjectionInterface.
   */
  public function setWebform(WebformInterface $webform);

  /**
   * Get the webform that this handler is attached to.
   *
   * @return \Drupal\webform\WebformInterface
   *   A webform.
   *
   * @todo Webform 8.x-6.x: Replace with WebformEntityInjectionInterface.
   */
  public function getWebform();

  /**
   * Set the webform submission that this handler is handling.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return $this
   *   This webform handler.
   *
   * @todo Webform 8.x-6.x: Replace with WebformEntityInjectionInterface.
   */
  public function setWebformSubmission(WebformSubmissionInterface $webform_submission = NULL);

  /**
   * Get the webform submission that this handler is handling.
   *
   * @return \Drupal\webform\WebformSubmissionInterface
   *   A webform submission.
   *
   * @todo Webform 8.x-6.x: Replace with WebformEntityInjectionInterface.
   */
  public function getWebformSubmission();

  /**
   * Check handler conditions against a webform submission.
   *
   * Note: Conditions are only applied to callbacks that require a
   * webform submissions.
   *
   * Conditions are ignored by…
   * - \Drupal\webform\Plugin\WebformHandlerInterface::alterElements
   * - \Drupal\webform\Plugin\WebformHandlerInterface::preCreate
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return bool
   *   TRUE if handler is disable or webform submission passes conditions.
   *   FALSE if webform submission fails conditions.
   */
  public function checkConditions(WebformSubmissionInterface $webform_submission);

  /****************************************************************************/
  // Webform methods.
  /****************************************************************************/

  /**
   * Alter webform submission webform elements.
   *
   * Note: This hook is ignored by conditional logic.
   *
   * @param array $elements
   *   An associative array containing the webform elements.
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   */
  public function alterElements(array &$elements, WebformInterface $webform);

  /**
   * Alter webform element.
   *
   * @param array $element
   *   The webform element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $context
   *   An associative array containing the following key-value pairs:
   *   - form: The form structure to which elements is being attached.
   *
   * @see \Drupal\webform\WebformSubmissionForm::prepareElements()
   * @see hook_webform_element_alter()
   */
  public function alterElement(array &$element, FormStateInterface $form_state, array $context);

  /****************************************************************************/
  // Webform submission methods.
  /****************************************************************************/

  /**
   * Alter/override a webform submission webform settings.
   *
   * IMPORTANT: Webform settings are overridden for just the webform submission.
   * Overridden settings are never saved to the Webform's configuration.
   *
   * @param array $settings
   *   An associative array containing the webform settings.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   */
  public function overrideSettings(array &$settings, WebformSubmissionInterface $webform_submission);

  /****************************************************************************/
  // Submission form methods.
  /****************************************************************************/

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

  /****************************************************************************/
  // Submission methods.
  /****************************************************************************/

  /**
   * Changes the values of an entity before it is created.
   *
   * Note: This hook is ignored by conditional logic.
   *
   * @param mixed[] $values
   *   An array of values to set, keyed by property name.
   */
  public function preCreate(array &$values);

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
   * Controls entity operation access to webform submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param string $operation
   *   The operation that is to be performed on $entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account trying to access the entity.
   *
   * @return \Drupal\Core\Core\AccessResultInterface
   *   The result of the access check. No option returns a nuetral result.
   */
  public function access(WebformSubmissionInterface $webform_submission, $operation, AccountInterface $account = NULL);

  /****************************************************************************/
  // Preprocessing methods.
  /****************************************************************************/

  /**
   * Prepares variables for webform confirmation templates.
   *
   * Default template: webform-confirmation.html.twig.
   *
   * @param array $variables
   *   An associative array containing the following key:
   *   - webform: A webform.
   *   - webform_submission: A webform submission.
   *   - source_entity: A webform submission source entity.
   */
  public function preprocessConfirmation(array &$variables);

  /****************************************************************************/
  // Handler methods.
  /****************************************************************************/

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

  /****************************************************************************/
  // Element methods.
  /****************************************************************************/

  /**
   * Controls entity operation access to webform submission element.
   *
   * @param array $element
   *   The element's properties.
   * @param string $operation
   *   The operation that is to be performed on $entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account trying to access the entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The result of the access check. Defaults to neutral.
   */
  public function accessElement(array &$element, $operation, AccountInterface $account = NULL);

  /**
   * Acts on a element after it has been created.
   *
   * @param string $key
   *   The element's key.
   * @param array $element
   *   The element's properties.
   */
  public function createElement($key, array $element);

  /**
   * Acts on a element after it has been updated.
   *
   * @param string $key
   *   The element's key.
   * @param array $element
   *   The element's properties.
   * @param array $original_element
   *   The original element's properties.
   */
  public function updateElement($key, array $element, array $original_element);

  /**
   * Acts on a element after it has been deleted.
   *
   * @param string $key
   *   The element's key.
   * @param array $element
   *   The element's properties.
   */
  public function deleteElement($key, array $element);

}
