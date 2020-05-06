<?php

namespace Drupal\webform_submission_log;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Interface for webform submission log manager.
 */
interface WebformSubmissionLogManagerInterface {

  /**
   * Insert submission log.
   *
   * @param array $fields
   *   An associative array of fields to be inserted into the submission log.
   */
  public function insert(array $fields);

  /**
   * Get webform submission log query.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $webform_entity
   *   A webform or webform submission entity.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   (optional) A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   * @param array $options
   *   (optional) Additional options and query conditions.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   A webform submission log select query.
   */
  public function getQuery(EntityInterface $webform_entity = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, array $options = []);

  /**
   * Log webform submission logs.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $webform_entity
   *   A webform or webform submission entity.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   (optional) A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   * @param array $options
   *   (optional) Additional options and query conditions.
   *
   * @return array
   *   An array of webform submission logs.
   *   Log entry object includes:
   *   - lid: (int) ID of the log entry.
   *   - sid: (int) Webform submission ID on which the operation was executed.
   *   - uid: (int) UID of the user that executed the operation.
   *   - handler_id: (string) Optional name of the handler that executed the
   *     operation.
   *   - operation: (string) Name of the executed operation.
   *   - message: (string) Untranslated message of the executed operation.
   *   - variables: (array) Variables to use whenever message has to be
   *     translated.
   *   - data: (array) Data associated with this log entry.
   *   - timestamp: (int) Timestamp when the operation was executed.
   */
  public function loadByEntities(EntityInterface $webform_entity = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, array $options = []);

}
