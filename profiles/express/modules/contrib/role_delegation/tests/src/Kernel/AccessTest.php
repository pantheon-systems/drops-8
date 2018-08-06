<?php

/**
 * @file
 * Contains \Drupal\Tests\role_delegation\Kernel\AccessTest
 */

namespace Drupal\Tests\role_delegation\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\simpletest\UserCreationTrait;

/**
 * @coversDefaultClass \Drupal\role_delegation\Access\RoleDelegationAccessCheck
 *
 * @group role_delegation
 */
class AccessTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * The modules to enable for this test.
   *
   * @var array
   */
  public static $modules = ['system', 'role_delegation', 'user'];

  /**
   * The Role Delegation access checker.
   *
   * @var \Drupal\role_delegation\Access\RoleDelegationAccessCheck
   */
  protected $accessChecker;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('user');
    $this->accessChecker = $this->container->get('access_check.role_delegation');

    // User 1 is still a super user so we create that user first so moving
    // forward we're just using normal users.
    $this->createUser([]);
  }

  /**
   * Test the access checker for user/%/roles.
   *
   * @covers ::access
   */
  public function testRoleDelegationAccess() {
    // Anonymous users can never access the roles page.
    $account = $this->createUser([]);
    $this->assertEquals(FALSE, $this->accessChecker->access($account)->isAllowed());

    // Users with "administer permissions" cannot view the page, they must use
    // the normal user edit page or also "have assign all roles".
    $account = $this->createUser(['administer permissions']);
    $this->assertEquals(FALSE, $this->accessChecker->access($account)->isAllowed());

    // Users with a custom "assign %custom role" permission should be able to
    // see the role admin page.
    $role = $this->createRole([]);
    $account = $this->createUser([sprintf('assign %s role', $role)]);
    $this->assertEquals(TRUE, $this->accessChecker->access($account)->isAllowed());

    // Users with 'assign all roles' can view the page.
    $account = $this->createUser(['assign all roles']);
    $this->assertEquals(TRUE, $this->accessChecker->access($account)->isAllowed());
  }

}
