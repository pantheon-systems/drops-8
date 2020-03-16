<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\Tests\BrowserTestBase;

/**
 * Verify handling of maintenance mode pages.
 *
 * @group metatag
 */
class MaintenanceMode extends BrowserTestBase {

  // Contains helper methods.
  use MetatagHelperTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // Modules for core functionality.
    'node',
    'field',
    'field_ui',
    'user',

    // Contrib dependencies.
    'token',

    // This module.
    'metatag',
  ];

  /**
   * Put the site into maintenance mode, see what the meta tags are.
   */
  public function testUser1() {
    // Load the user 1 profile page.
    $this->drupalGet('/user/1');
    // Confirm the page title is correct.
    $this->assertRaw('<title>Access denied | ');
    $this->assertNoRaw('<title>admin | ');
    $this->assertNoRaw('<title>Site under maintenance | ');

    // Put the site into maintenance mode.
    \Drupal::state()->set('system.maintenance_mode', TRUE);
    Cache::invalidateTags(['rendered']);

    // Load the user 1 profile page again.
    $this->drupalGet('/user/1');
    // Confirm the page title has changed.
    $this->assertNoRaw('<title>Access denied | ');
    $this->assertNoRaw('<title>admin | ');
    $this->assertRaw('<title>Site under maintenance | ');
  }

}
