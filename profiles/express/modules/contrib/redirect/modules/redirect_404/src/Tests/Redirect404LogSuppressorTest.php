<?php

namespace Drupal\redirect_404\Tests;

/**
 * Tests suppressing 404 logs if the suppress_404 setting is enabled.
 *
 * @group redirect_404
 */
class Redirect404LogSuppressorTest extends Redirect404TestBase {

  /**
   * Additional modules to enable.
   *
   * @var array
   */
  public static $modules = ['dblog'];

  /**
   * A user with some relevant administrative permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A user without any permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create users with specific permissions.
    $this->adminUser = $this->drupalCreateUser([
      'administer redirect settings',
      'access site reports',
    ]);
    $this->webUser = $this->drupalCreateUser([]);
  }

  /**
   * Tests the suppress_404 service.
   */
  public function testSuppress404Events() {
    // Cause a page not found and an access denied event.
    $this->drupalGet('page-not-found');
    $this->assertResponse(404);
    $this->drupalLogin($this->webUser);
    $this->drupalGet('admin/reports/dblog');
    $this->assertResponse(403);

    // Assert the events are logged in the dblog reports.
    $this->assertEqual(db_query("SELECT COUNT(*) FROM {watchdog} WHERE type = 'page not found'")->fetchField(), 1);
    $this->assertEqual(db_query("SELECT COUNT(*) FROM {watchdog} WHERE type = 'access denied'")->fetchField(), 1);

    // Login as admin and enable suppress_404 to avoid logging the 404 event.
    $this->drupalLogin($this->adminUser);
    $edit = ['suppress_404' => TRUE];
    $this->drupalPostForm('admin/config/search/redirect/settings', $edit, 'Save configuration');

    // Cause again a page not found and an access denied event.
    $this->drupalGet('page-not-found');
    $this->assertResponse(404);
    $this->drupalLogin($this->webUser);
    $this->drupalGet('admin/reports/dblog');
    $this->assertResponse(403);

    // Assert only the new access denied event is logged now.
    $this->drupalLogin($this->adminUser);
    $this->assertEqual(db_query("SELECT COUNT(*) FROM {watchdog} WHERE type = 'page not found'")->fetchField(), 1);
    $this->assertEqual(db_query("SELECT COUNT(*) FROM {watchdog} WHERE type = 'access denied'")->fetchField(), 2);

  }
}
