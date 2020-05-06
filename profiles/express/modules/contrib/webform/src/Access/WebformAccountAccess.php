<?php

namespace Drupal\webform\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the custom access control handler for the user accounts.
 */
class WebformAccountAccess {

  /**
   * Check whether the user has 'administer webform' or 'administer webform submission' permission.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkAdminAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermissions($account, ['administer webform', 'administer webform submission'], 'OR');
  }

  /**
   * Check whether the user has 'administer' or 'overview' permission.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkOverviewAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermissions($account, ['administer webform', 'administer webform submission', 'access webform overview'], 'OR');
  }

  /**
   * Check whether the user has 'overview' with 'create' permission.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkTemplatesAccess(AccountInterface $account) {
    $condition = ($account->hasPermission('access webform overview') &&
      ($account->hasPermission('administer webform') || $account->hasPermission('create webform')));
    return AccessResult::allowedIf($condition)->cachePerPermissions();
  }

  /**
   * Check whether the user can view submissions.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkSubmissionAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermissions($account, ['administer webform', 'administer webform submission', 'view any webform submission'], 'OR');
  }

  /**
   * Check whether the user can view own submissions.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkUserSubmissionsAccess(AccountInterface $account) {
    $condition = ($account->hasPermission('administer webform') || $account->hasPermission('administer webform submission') || $account->hasPermission('view any webform submission'))
      || ($account->hasPermission('access webform submission user') && \Drupal::currentUser()->id() === $account->id());
    return AccessResult::allowedIf($condition)->cachePerPermissions();
  }

}
