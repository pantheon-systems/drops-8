<?php

namespace Drupal\webform;

use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Defines an interface for webform submission classes.
 */
interface WebformSubmissionStorageInterface extends ContentEntityStorageInterface {

  /**
   * Return status for saving of webform submission when saving results is disabled.
   */
  const SAVED_DISABLED = 0;

  /**
   * Denote not to purge automatically anything at all.
   *
   * @var string
   */
  const PURGE_NONE = 'none';

  /**
   * Denote to purge automatically only drafts.
   *
   * @var string
   */
  const PURGE_DRAFT = 'draft';

  /**
   * Denote to purge automatically only completed submissions.
   *
   * @var string
   */
  const PURGE_COMPLETED = 'completed';

  /**
   * Denote to purge automatically all submissions.
   *
   * @var string
   */
  const PURGE_ALL = 'all';

  /**
   * Get webform submission entity field definitions.
   *
   * The helper method is generally used for exporting results.
   *
   * @see \Drupal\webform\Element\WebformExcludedColumns
   * @see \Drupal\webform\Controller\WebformResultsExportController
   *
   * @return array
   *   An associative array of field definition key by field name containing
   *   title, name, and datatype.
   */
  public function getFieldDefinitions();

  /**
   * Check field definition access.
   *
   * Access checks include...
   * - Only allowing user who can update any access to the 'token' field.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform to check field definition access.
   * @param array $definitions
   *   Field definitions.
   *
   * @return array
   *   Field definitions with access checked.
   */
  public function checkFieldDefinitionAccess(WebformInterface $webform, array $definitions);

  /**
   * Load submission using webform (secure) token.
   *
   * @param string $token
   *   The submission (secure) token.
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform that the submission token is associated with.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   (optional) A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   (optional) A user account.
   *
   * @return \Drupal\webform\WebformSubmissionInterface|null
   *   A webform submission.
   */
  public function loadFromToken($token, WebformInterface $webform, EntityInterface $source_entity = NULL, AccountInterface $account = NULL);

  /**
   * Delete all webform submissions.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   (optional) The webform to delete the submissions from.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   (optional) A webform submission source entity.
   * @param int $limit
   *   (optional) Number of submissions to be deleted.
   * @param int $max_sid
   *   (optional) Maximum webform submission id.
   *
   * @return int
   *   The number of webform submissions deleted.
   */
  public function deleteAll(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, $limit = NULL, $max_sid = NULL);

  /**
   * Get the total number of submissions.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   (optional) A webform. If set the total number of submissions for the
   *   Webform will be returned.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   (optional) A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   (optional) A user account.
   * @param bool $in_draft
   *   (optional) Look for submissions in draft. Defaults to FALSE.
   *   Setting to NULL will return all saved submissions and drafts.
   *
   * @return int
   *   Total number of submissions.
   */
  public function getTotal(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, $in_draft = FALSE);

  /**
   * Get the maximum sid.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   (optional) A webform. If set the total number of submissions for the
   *   Webform will be returned.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   (optional) A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   (optional) A user account.
   *
   * @return int
   *   Total number of submissions.
   */
  public function getMaxSubmissionId(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL);

  /**
   * Determine if a webform element has submission values.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param string $element_key
   *   An element key.
   *
   * @return bool
   *   TRUE if a webform element has submission values.
   */
  public function hasSubmissionValue(WebformInterface $webform, $element_key);

  /****************************************************************************/
  // Query methods.
  /****************************************************************************/

  /**
   * Add condition to submission query.
   *
   * @param \Drupal\Core\Database\Query\AlterableInterface $query
   *   The query instance.
   * @param \Drupal\webform\WebformInterface $webform
   *   (optional) A webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   (optional) A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The current user account.
   * @param array $options
   *   (optional) Additional options and query conditions.
   */
  public function addQueryConditions(AlterableInterface $query, WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, array $options = []);

  /****************************************************************************/
  // Paging methods.
  /****************************************************************************/

  /**
   * Get a webform's first submission.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   (optional) A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   * @param array $options
   *   (optional) Additional options and query conditions.
   *
   * @return \Drupal\webform\WebformSubmissionInterface|null
   *   The webform's first submission.
   */
  public function getFirstSubmission(WebformInterface $webform, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, array $options = []);

  /**
   * Get a webform's last submission.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   (optional) A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   * @param array $options
   *   (optional) Additional options and query conditions.
   *
   * @return \Drupal\webform\WebformSubmissionInterface|null
   *   The webform's last submission.
   */
  public function getLastSubmission(WebformInterface $webform, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, array $options = []);

  /**
   * Get a webform submission's previous sibling.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   (optional) A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   * @param array $options
   *   (optional) Additional options and query conditions.
   *
   * @return \Drupal\webform\WebformSubmissionInterface|null
   *   The webform submission's previous sibling.
   */
  public function getPreviousSubmission(WebformSubmissionInterface $webform_submission, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, array $options = []);

  /**
   * Get a webform submission's next sibling.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   (optional) A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   * @param array $options
   *   (optional) Additional options and query conditions.
   *
   * @return \Drupal\webform\WebformSubmissionInterface|null
   *   The webform submission's next sibling.
   */
  public function getNextSubmission(WebformSubmissionInterface $webform_submission, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, array $options = []);

  /**
   * Get webform submission source entity types.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return array
   *   An array of entity types that the webform has been submitted from.
   */
  public function getSourceEntityTypes(WebformInterface $webform);

  /****************************************************************************/
  // WebformSubmissionEntityList methods.
  /****************************************************************************/

  /**
   * Get customized submission columns used to display custom table.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   A webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   A user account.
   * @param bool $include_elements
   *   Flag that include all form element in the list of columns.
   *
   * @return array|mixed
   *   An associative array of columns keyed by name.
   */
  public function getCustomColumns(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, $include_elements = TRUE);

  /**
   * Get user submission columns used to display results.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   A webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   A user account.
   * @param bool $include_elements
   *   Flag that include all form element in the list of columns.
   *
   * @return array|mixed
   *   An associative array of columns keyed by name.
   */
  public function getUserColumns(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, $include_elements = TRUE);

  /**
   * Get user default submission columns used to display results.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   A webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   A user account.
   * @param bool $include_elements
   *   Flag that include all form element in the list of columns.
   *
   * @return array|mixed
   *   An associative array of columns names.
   */
  public function getUserDefaultColumnNames(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, $include_elements = TRUE);

  /**
   * Get default submission columns used to display results.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   A webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   A user account.
   * @param bool $include_elements
   *   Flag that include all form element in the list of columns.
   *
   * @return array|mixed
   *   An associative array of columns keyed by name.
   */
  public function getDefaultColumns(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, $include_elements = TRUE);

  /**
   * Get submission columns used to display results table.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   A webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   A user account.
   * @param bool $include_elements
   *   Flag that include all form element in the list of columns.
   *
   * @return array|mixed
   *   An associative array of columns keyed by name.
   */
  public function getColumns(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, $include_elements = TRUE);

  /**
   * Get customize setting.
   *
   * @param string $name
   *   Custom settings name.
   * @param mixed $default
   *   Custom settings default value.
   * @param \Drupal\webform\WebformInterface|null $webform
   *   A webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   A webform submission source entity.
   *
   * @return mixed
   *   Custom setting.
   */
  public function getCustomSetting($name, $default, WebformInterface $webform = NULL, EntityInterface $source_entity = NULL);

  /****************************************************************************/
  // Invoke methods.
  /****************************************************************************/

  /**
   * Invoke a webform submission's webform's handlers method.
   *
   * @param string $method
   *   The webform handler method to be invoked.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param mixed $context1
   *   (optional) An additional variable that is passed by reference.
   * @param mixed $context2
   *   (optional) An additional variable that is passed by reference.
   */
  public function invokeWebformHandlers($method, WebformSubmissionInterface $webform_submission, &$context1 = NULL, &$context2 = NULL);

  /**
   * Invoke a webform submission's webform's elements method.
   *
   * @param string $method
   *   The webform element method to be invoked.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param mixed $context1
   *   (optional) An additional variable that is passed by reference.
   * @param mixed $context2
   *   (optional) An additional variable that is passed by reference.
   */
  public function invokeWebformElements($method, WebformSubmissionInterface $webform_submission, &$context1 = NULL, &$context2 = NULL);

  /****************************************************************************/
  // Purge methods.
  /****************************************************************************/

  /**
   * Purge webform submissions.
   *
   * @param int $count
   *   Amount of webform submissions to purge.
   */
  public function purge($count);

  /****************************************************************************/
  // Data handlers.
  /****************************************************************************/

  /**
   * Save webform submission data to the 'webform_submission_data' table.
   *
   * This method is public the allow webform handler (i.e. remote posts) to
   * update [webform:handler] tokens stored in the submission data.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param bool $delete_first
   *   TRUE to delete any data first. For new submissions this is not needed.
   *
   * @see \Drupal\webform\Plugin\WebformHandler\RemotePostWebformHandler::remotePost
   */
  public function saveData(WebformSubmissionInterface $webform_submission, $delete_first = TRUE);

  /****************************************************************************/
  // Log methods.
  /****************************************************************************/

  /**
   * Write an event to the webform submission log.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $values
   *   The value to be logged includes 'handler_id', 'operation', 'message', and 'data'.
   */
  public function log(WebformSubmissionInterface $webform_submission, array $values = []);

  /****************************************************************************/
  // Draft methods.
  /****************************************************************************/

  /**
   * Get webform submission draft.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   A webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   A user account.
   *
   * @return \Drupal\webform\WebformSubmissionInterface
   *   A webform submission.
   */
  public function loadDraft(WebformInterface $webform, EntityInterface $source_entity = NULL, AccountInterface $account = NULL);

  /**
   * React to an event when a user logs in.
   *
   * @param \Drupal\user\UserInterface $account
   *   Account that has just logged in.
   */
  public function userLogin(UserInterface $account);

}
