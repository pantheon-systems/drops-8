<?php

/**
 * @file
 * Contains \Drupal\role_delegation\Tests\RoleAssignTest.
 */

namespace Drupal\role_delegation\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\user\Entity\User;

/**
 * Functional tests for assigning roles.
 *
 * @group role_delegation
 */
class RoleAssignTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['user', 'role_delegation'];

  /**
   * Ensure we can only see the roles we have permission to assign.
   */
  public function testRoleAccess() {
    $rid1 = $this->drupalCreateRole([]);
    $rid2 = $this->drupalCreateRole([]);
    $rid3 = $this->drupalCreateRole([]);

    $current_user = $this->drupalCreateUser(["assign $rid1 role", "assign $rid2 role"]);
    $this->drupalLogin($current_user);

    // Only 2 of the 3 roles appear on any user edit page.
    $account = $this->drupalCreateUser();
    $this->drupalGet(sprintf('/user/%s/roles', $account->id()));
    $this->assertFieldByName("role_change[$rid1]");
    $this->assertFieldByName("role_change[$rid2]");
    $this->assertNoFieldByName("role_change[$rid3]");
  }

  /**
   * Test that we can assign roles we have access to.
   */
  public function testRoleAssign() {
    // Create a role and login as a user with the permission to assign it.
    $rid1 = $this->drupalCreateRole([]);
    $current_user = $this->drupalCreateUser(["assign $rid1 role"]);
    $this->drupalLogin($current_user);

    // Go to the users roles edit page.
    $account = $this->drupalCreateUser();
    $this->drupalGet(sprintf('/user/%s/roles', $account->id()));

    // The form element field id and name.
    $field_id = "edit-role-change-$rid1";
    $field_name = "role_change[$rid1]";

    // Ensure its disabled by default.
    $this->assertNoFieldChecked($field_id, 'Role is not assigned by default.');
    $this->assertFalse($account->hasPermission("assign $rid1 role"), 'Role is not assigned by default.');

    // Assign the role and ensure its now checked and assigned.
    $this->drupalPostForm(NULL, [$field_name => $rid1], 'Save');
    $this->assertFieldChecked($field_id, 'Role has been granted.');
    \Drupal::entityTypeManager()->clearCachedDefinitions();
    $account = User::load($account->id());
    $this->assertTrue($account->hasRole($rid1), 'Role has been granted');

    // Revoke the role.
    $this->drupalPostForm(NULL, [$field_name => FALSE], 'Save');
    $this->assertNoFieldChecked($field_id, 'Role has been revoked.');
    \Drupal::entityTypeManager()->clearCachedDefinitions();
    $account = User::load($account->id());
    $this->assertFalse($account->hasRole($rid1), 'Role has been revoked.');
  }

}
