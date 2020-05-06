<?php

namespace Drupal\webform_access\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the custom access control handler for the webform access groups.
 */
class WebformAccessGroupAccess {

  /**
   * Check whether the current user is a administor or assign admin access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkAdminAccess() {
    $account = \Drupal::currentUser();
    if ($account->hasPermission('administer webform')) {
      $access_result = AccessResult::allowed();
    }
    elseif (self::isAdmin($account)) {
      $access_result = AccessResult::allowed();
      $access_result->addCacheTags(['webform_access_group_list']);
    }
    else {
      $access_result = AccessResult::neutral();
    }

    return $access_result->addCacheableDependency($account);
  }

  /**
   * Determine if a user account is am administrator for any access group.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user account.
   *
   * @return bool
   *   TRUE if a user account is am administrator for any access group.
   */
  protected static function isAdmin(AccountInterface $account) {
    return \Drupal::database()->select('webform_access_group_admin', 'gu')
      ->fields('gu', ['group_id'])
      ->condition('uid', $account->id())
      ->range(0, 1)
      ->execute()
      ->fetchField() ? TRUE : FALSE;
  }

}
