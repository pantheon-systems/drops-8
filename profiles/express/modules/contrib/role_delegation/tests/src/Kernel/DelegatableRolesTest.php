<?php

namespace Drupal\Tests\role_delegation\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\simpletest\UserCreationTrait;
use Drupal\user\Entity\Role;

/**
 * @coversDefaultClass \Drupal\role_delegation\DelegatableRoles
 *
 * @group role_delegation
 */
class DelegatableRolesTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * The modules to enable for this test.
   *
   * @var array
   */
  public static $modules = ['system', 'role_delegation', 'user'];

  /**
   * The Role Delegation service.
   *
   * @var \Drupal\role_delegation\DelegatableRolesInterface
   */
  protected $delegatableRoles;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('user');
    $this->delegatableRoles = $this->container->get('delegatable_roles');

    // User 1 is still a super user so we create that user first so moving
    // forward we're just using normal users.
    $this->createUser([]);
  }

  /**
   * Test the roles that can be assigned by a given user.
   *
   * @covers ::getAssignableRoles
   */
  public function testAssignableRoles() {
    $rid1 = $this->createRole([]);
    $rid2 = $this->createRole([]);
    $rid3 = $this->createRole([]);

    // Test the 'assign all roles permission'. We have to merge in the roles of
    // the account as well because createUser() creates a new role.
    $account = $this->createUser(['assign all roles']);
    $this->assertEquals(array_merge([$rid1, $rid2, $rid3], $account->getRoles(TRUE)), array_keys($this->delegatableRoles->getAssignableRoles($account)));

    // If they have these two roles, they can assign exactly those two roles.
    $account = $this->createUser(["assign $rid1 role", "assign $rid2 role"]);
    $this->assertEquals([$rid1, $rid2], array_keys($this->delegatableRoles->getAssignableRoles($account)));

    // Doesn't matter what permissions they have here, they can never assign
    // anonymous or authenticated roles.
    $account = $this->createUser(['administer users', 'administer permissions']);
    $this->assertEquals([], $this->delegatableRoles->getAssignableRoles($account));
  }

  /**
   * Test the all roles methods filters special roles.
   *
   * @covers ::getAllRoles
   */
  public function testGetAllRoles() {
    $rid1 = $this->createRole([]);
    $rid2 = $this->createRole([]);
    $this->assertEquals([$rid1, $rid2], array_keys($this->delegatableRoles->getAllRoles()));
  }

  /**
   * Deleting a role revokes the permission allowing users to assign the role.
   */
  public function testDeleteRole() {
    $rid = $this->createRole([]);
    $permission = "assign $rid role";
    $account = $this->createUser([$permission]);
    $this->assertTrue($account->hasPermission($permission), sprintf('User has "%s" permission.', $permission));

    // Delete the role and ensure the user no longer has the permission.
    Role::load($rid)->delete();
    $this->assertFalse($account->hasPermission($permission), sprintf('User no longer has "%s" permission.', $permission));
  }

}
