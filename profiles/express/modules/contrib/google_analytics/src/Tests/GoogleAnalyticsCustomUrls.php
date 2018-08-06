<?php

namespace Drupal\google_analytics\Tests;

use Drupal\Core\Session\AccountInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Test custom url functionality of Google Analytics module.
 *
 * @group Google Analytics
 */
class GoogleAnalyticsCustomUrls extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['google_analytics'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'access administration pages',
      'administer google analytics',
      'administer modules',
      'administer site configuration',
    ];

    // User to set up google_analytics.
    $this->admin_user = $this->drupalCreateUser($permissions);
  }

  /**
   * Tests if user password page urls are overridden.
   */
  public function testGoogleAnalyticsUserPasswordPage() {
    $base_path = base_path();
    $ua_code = 'UA-123456-1';
    $this->config('google_analytics.settings')->set('account', $ua_code)->save();

    $this->drupalGet('user/password', ['query' => ['name' => 'foo']]);
    $this->assertRaw('ga("set", "page", "' . $base_path . 'user/password"');

    $this->drupalGet('user/password', ['query' => ['name' => 'foo@example.com']]);
    $this->assertRaw('ga("set", "page", "' . $base_path . 'user/password"');

    $this->drupalGet('user/password');
    $this->assertNoRaw('ga("set", "page",', '[testGoogleAnalyticsCustomUrls]: Custom url not set.');
  }

}
