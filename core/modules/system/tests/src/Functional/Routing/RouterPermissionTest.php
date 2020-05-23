<?php

namespace Drupal\Tests\system\Functional\Routing;

use Drupal\Tests\BrowserTestBase;

/**
 * Function Tests for the routing permission system.
 *
 * @group Routing
 */
class RouterPermissionTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['router_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests permission requirements on routes.
   */
  public function testPermissionAccess() {
    // Ensure 403 Access Denied for a route without permission.
    $this->drupalGet('router_test/test7');
    $this->assertResponse(403);

    // Ensure 403 Access Denied by default if no access specified.
    $this->drupalGet('router_test/test8');
    $this->assertResponse(403);

    $user = $this->drupalCreateUser(['access test7']);
    $this->drupalLogin($user);
    $this->drupalGet('router_test/test7');
    $this->assertResponse(200);
    $this->assertNoRaw('Access denied');
    $this->assertRaw('test7text', 'The correct string was returned because the route was successful.');
  }

}
