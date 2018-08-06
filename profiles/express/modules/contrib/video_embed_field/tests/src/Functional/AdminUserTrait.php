<?php

namespace Drupal\Tests\video_embed_field\Functional;

use Drupal\simpletest\UserCreationTrait;

/**
 * Create admin users.
 */
trait AdminUserTrait {

  use UserCreationTrait;

  /**
   * Create an admin user.
   *
   * @return \Drupal\user\UserInterface
   *   A user with all permissions.
   */
  protected function createAdminUser() {
    return $this->drupalCreateUser(array_keys($this->container->get('user.permissions')->getPermissions()));
  }

}
