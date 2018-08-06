<?php

namespace Drupal\role_delegation;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

class DelegatableRoles implements DelegatableRolesInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getAssignableRoles(AccountInterface $account) {
    $assignable_roles = [];
    foreach ($this->getAllRoles() as $role) {
      if ($account->hasPermission(sprintf('assign %s role', $role->id())) || $account->hasPermission('assign all roles')) {
        $assignable_roles[$role->id()] = $role->label();
      }
    }
    return $assignable_roles;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllRoles() {
    $all_roles = $roles = Role::loadMultiple();;
    unset($all_roles[RoleInterface::ANONYMOUS_ID]);
    unset($all_roles[RoleInterface::AUTHENTICATED_ID]);
    return $all_roles;
  }

}
