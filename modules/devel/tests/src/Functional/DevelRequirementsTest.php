<?php

namespace Drupal\Tests\devel\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests devel requirements.
 *
 * @group devel
 */
class DevelRequirementsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['devel'];

  /**
   * Tests that the status page shows a warning when evel is enabled.
   */
  public function testStatusPage() {
    $admin_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/reports/status');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->pageTextContains('Devel module enabled');
    $this->assertSession()->pageTextContains('The module provide the access to debug informations, therefore is recommended to disable the Devel module on production sites.');
  }

}
