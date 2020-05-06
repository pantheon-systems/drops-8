<?php

namespace Drupal\Tests\webform\Unit\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\webform\Access\WebformAccountAccess;

/**
 * @coversDefaultClass \Drupal\webform\Access\WebformAccountAccess
 *
 * @group webform
 */
class WebformAccountAccessTest extends WebformAccessTestBase {

  /**
   * Tests the check webform account access.
   *
   * @covers ::checkAdminAccess
   * @covers ::checkSubmissionAccess
   * @covers ::checkOverviewAccess
   */
  public function testWebformAccountAccess() {
    // Mock anonymous account.
    $anonymous_account = $this->mockAccount();

    // Mock admin account.
    $admin_account = $this->mockAccount([
      'administer webform' => TRUE,
      'administer webform submission' => TRUE,
    ]);

    // Mock submission account.
    $submission_account = $this->mockAccount([
      'access webform overview' => TRUE,
      'view any webform submission' => TRUE,
    ]);

    /**************************************************************************/

    // Check admin access.
    $this->assertEquals(AccessResult::neutral()->cachePerPermissions(), WebformAccountAccess::checkAdminAccess($anonymous_account)->setReason(''));
    $this->assertEquals(AccessResult::allowed()->cachePerPermissions(), WebformAccountAccess::checkAdminAccess($admin_account));

    // Check submission access.
    $this->assertEquals(AccessResult::neutral()->cachePerPermissions(), WebformAccountAccess::checkSubmissionAccess($anonymous_account)->setReason(''));
    $this->assertEquals(AccessResult::allowed()->cachePerPermissions(), WebformAccountAccess::checkSubmissionAccess($submission_account));

    // Check overview access.
    $this->assertEquals(AccessResult::neutral()->cachePerPermissions(), WebformAccountAccess::checkOverviewAccess($anonymous_account)->setReason(''));
    $this->assertEquals(AccessResult::allowed()->cachePerPermissions(), WebformAccountAccess::checkOverviewAccess($submission_account));
  }

}
