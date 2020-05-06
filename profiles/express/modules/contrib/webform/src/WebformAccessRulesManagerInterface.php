<?php

namespace Drupal\webform;

use Drupal\Core\Session\AccountInterface;

/**
 * Interface of webform access rules manager.
 */
interface WebformAccessRulesManagerInterface {

  /**
   * Check if operation is allowed through access rules for a given webform.
   *
   * @param string $operation
   *   Operation to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account who is requesting the operation.
   * @param \Drupal\webform\WebformInterface $webform
   *   Webform on which the operation is requested.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Access result.
   */
  public function checkWebformAccess($operation, AccountInterface $account, WebformInterface $webform);

  /**
   * Check if operation is allowed through access rules for a submission.
   *
   * @param string $operation
   *   Operation to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account who is requesting the operation.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   Webform submission on which the operation is requested.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Access result.
   */
  public function checkWebformSubmissionAccess($operation, AccountInterface $account, WebformSubmissionInterface $webform_submission);

  /****************************************************************************/
  // Get access rules methods.
  /****************************************************************************/

  /**
   * Returns the webform default access rules.
   *
   * @return array
   *   A structured array containing all the webform default access rules.
   */
  public function getDefaultAccessRules();

  /**
   * Retrieve a list of access rules from a webform.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   Webform whose access rules to retrieve.
   *
   * @return array
   *   Associative array of access rules contained in the provided webform. Keys
   *   are operation names whereas values are sub arrays with the following
   *   structure:
   *   - roles: (array) Array of roles that should have access to this operation
   *   - users: (array) Array of UIDs that should have access to this operation
   *   - permissions: (array) Array of permissions that should grant access to
   *     this operation
   */
  public function getAccessRules(WebformInterface $webform);

  /****************************************************************************/
  // Check access rules methods.
  /****************************************************************************/

  /**
   * Check access for a given operation and set of access rules.
   *
   * @param string $operation
   *   Operation that is being requested.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account that is requesting access to the operation.
   * @param array $access_rules
   *   A set of access rules to check against.
   *
   * @return bool
   *   TRUE if access is allowed and FALSE is access is denied.
   */
  public function checkAccessRules($operation, AccountInterface $account, array $access_rules);

  /**
   * Collect metadata on known access rules.
   *
   * @return array
   *   Array that describes all known access rules. It will be keyed by access
   *   rule machine-name and will contain sub arrays with the following
   *   structure:
   *   - title: (string) Human-friendly translated string that describes the
   *     meaning of this access rule.
   *   - description: (array) Renderable array that explains what this access rule
   *     stands for. Defaults to an empty array.
   *   - roles: (string[]) Array of role IDs that should be granted this access
   *     rule by default. Defaults to an empty array.
   *   - permissions: (string[]) Array of permissions that should be granted this
   *     access rule by default. Defaults to an empty array.
   */
  public function getAccessRulesInfo();

  /**
   * Determine if access rules should be cached per user.
   *
   * @param array $access_rules
   *   A set of access rules.
   *
   * @return bool
   *   TRUE if access rules should be cached per user.
   */
  public function cachePerUser(array $access_rules);

}
