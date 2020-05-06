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
   * Access checks include…
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
   * Load webform submissions by their related entity references.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   (optional) The webform that the submission token is associated with.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   (optional) A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   (optional) A user account.
   *
   * @return \Drupal\webform\WebformSubmissionInterface[]
   *   An array of webform submission objects indexed by their ids.
   */
  public function loadByEntities(WebformInterface $webform, EntityInterface $source_entity = NULL, AccountInterface $account = NULL);

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
   * @param array $options
   *   Options/conditions include:
   *   - in_draft (boolean): NULL will return all saved submissions and drafts.
   *     Defaults to FALSE.
   *   - interval (int): Limit total within an seconds interval.
   *
   * @return int
   *   Total number of submissions.
   */
  public function getTotal(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, array $options = []);

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
  // Source entity methods.
  /****************************************************************************/

  /**
   * Get total number of source entities.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return int
   *   Total number of source entities.
   */
  public function getSourceEntitiesTotal(WebformInterface $webform);

  /**
   * Get source entities associated for a specified webform.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return array
   *   An associative array contain source entities associated for
   *   a specified webform grouped by entity type.
   */
  public function getSourceEntities(WebformInterface $webform);

  /**
   * Get source entities as options for a specified webform.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return array
   *   An associative array contain ource entities as options for
   *   a specified webform.
   */
  public function getSourceEntitiesAsOptions(WebformInterface $webform);

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
   *   Options/conditions include:
   *   - in_draft (boolean): NULL will return all saved submissions and drafts.
   *     Defaults to NULL
   *   - check_source_entity (boolean): Check that a source entity is defined.
   *   - interval (int): Limit total within an seconds interval.
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

  /**
   * Get webform submission source entities as options.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param string $entity_type
   *   A source entity type.
   *
   * @return array
   *   An array of source entities as options that the webform
   *   has been submitted from.
   */
  public function getSourceEntityAsOptions(WebformInterface $webform, $entity_type);

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
   * @return array
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
   * @return array
   *   An associative array of columns keyed by name.
   */
  public function getUserColumns(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, $include_elements = TRUE);

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
   * @return array
   *   An associative array of columns keyed by name.
   */
  public function getDefaultColumns(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, $include_elements = TRUE);

  /**
   * Get submissions columns.
   *
   * @return array
   *   An associative array of columns keyed by name.
   */
  public function getSubmissionsColumns();

  /**
   * Get user submissions columns.
   *
   * @return array
   *   An associative array of columns keyed by name.
   */
  public function getUsersSubmissionsColumns();

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
   * @return array
   *   An associative array of columns keyed by name.
   */
  public function getColumns(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, $include_elements = TRUE);

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
   * @return array
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
   * @return array
   *   An associative array of columns names.
   */
  public function getDefaultColumnNames(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, $include_elements = TRUE);

  /****************************************************************************/
  // Custom settings methods.
  /****************************************************************************/

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
  // Custom CRUD methods.
  /****************************************************************************/

  /**
   * Resaves the entity without triggering any hooks or handlers.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to save.
   *
   * @return bool|int
   *   If the record insert or update failed, returns FALSE. If it succeeded,
   *   returns SAVED_NEW or SAVED_UPDATED, depending on the operation performed.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures, an exception is thrown.
   */
  public function resave(EntityInterface $entity);

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
   *
   * @return \Drupal\Core\Access\AccessResult|null
   *   If 'access' method is invoked an AccessResult is returned.
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
   * @param array $context
   *   The values/context to be logged includes 'handler_id', 'operation', 'message', and 'data'.
   *
   * @deprecated Instead call the 'webform_submission' logger channel directly.
   *
   *  $message = 'Some message with an %argument.'
   *  $context = [
   *    '%argument' => 'Some value'
   *    'link' => $webform_submission->toLink($this->t('Edit'), 'edit-form')->toString(),
   *    'webform_submission' => $webform_submission,
   *    'handler_id' => NULL,
   *    'data' => [],
   *  ];
   *  \Drupal::logger('webform_submission')->notice($message, $context);
   */
  public function log(WebformSubmissionInterface $webform_submission, array $context = []);

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

  /****************************************************************************/
  // Anonymous submission methods.
  /****************************************************************************/

  /**
   * React to an event when a user logs in.
   *
   * @param \Drupal\user\UserInterface $account
   *   Account that has just logged in.
   */
  public function userLogin(UserInterface $account);

  /**
   * Get anonymous user's submission ids.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   A user account.
   *
   * @return array|
   *   A array of submission ids or NULL if the user us not anonymous or has
   *   not saved submissions.
   */
  public function getAnonymousSubmissionIds(AccountInterface $account);

}
