<?php

/**
 * @file
 * Contains \Drupal\role_delegation\Access\RoleDelegationAccessCheck.
 */

namespace Drupal\role_delegation\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\role_delegation\PermissionGenerator;

/**
 * Checks access for displaying configuration edit user pages.
 */
class RoleDelegationAccessCheck implements AccessInterface {

  /**
   * The permission generator service.
   *
   * @var \Drupal\role_delegation\PermissionGenerator
   */
  protected $permissionGenerator;

  /**
   * The Role Delegation access check.
   *
   * @param \Drupal\role_delegation\PermissionGenerator $permission_generator
   *   The role delegation service.
   */
  public function __construct(PermissionGenerator $permission_generator) {
    $this->permissionGenerator = $permission_generator;
  }

  /**
   * Custom access check for the /user/%/roles page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    // If the user has any of the "assign custom role" permissions then we give
    // them access to the form.
    foreach ($this->permissionGenerator->rolePermissions() as $perm => $title) {
      if ($account->hasPermission($perm)) {
        return AccessResult::allowed()->cachePerPermissions();
      }
    }

    // If the user can administer all permissions then they can also view the
    // roles page.
    return AccessResult::allowedIfHasPermission($account, 'assign all roles');
  }

}
