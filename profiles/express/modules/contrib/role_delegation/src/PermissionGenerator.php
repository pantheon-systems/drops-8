<?php

namespace Drupal\role_delegation;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * The PermissionGenerator class.
 */
class PermissionGenerator {

  use StringTranslationTrait;

  /**
   * The delegatable role service for getting all the roles.
   *
   * @var \Drupal\role_delegation\DelegatableRolesInterface
   */
  protected $delegatableRoles;

  /**
   * Construct a new permission generator.
   *
   * @param \Drupal\role_delegation\DelegatableRolesInterface $delegatable_roles
   *   The delegatable roles service.
   */
  public function __construct(DelegatableRolesInterface $delegatable_roles) {
    $this->delegatableRoles = $delegatable_roles;
  }

  /**
   * Returns an array of permissions to assign specific roles.
   *
   * @return array
   *   An array of permissions in the correct format for permission_callbacks.
   */
  public function rolePermissions() {
    $perms = array();
    foreach ($this->delegatableRoles->getAllRoles() as $rid => $role) {
      $perms[sprintf('assign %s role', $rid)] = [
        'title' => $this->t('Assign %role role', ['%role' => $role->label()]),
      ];
    }

    return $perms;
  }

}
