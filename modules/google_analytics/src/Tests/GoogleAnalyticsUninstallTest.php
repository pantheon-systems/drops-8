<?php

namespace Drupal\google_analytics\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test uninstall functionality of Google Analytics module.
 *
 * @group Google Analytics
 */
class GoogleAnalyticsUninstallTest extends WebTestBase {

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
    ];

    // User to set up google_analytics.
    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Tests if the module cleans up the disk on uninstall.
   */
  public function testGoogleAnalyticsUninstall() {
    $cache_path = 'public://google_analytics';
    $ua_code = 'UA-123456-1';

    // Show tracker in pages.
    $this->config('google_analytics.settings')->set('account', $ua_code)->save();

    // Enable local caching of analytics.js.
    $this->config('google_analytics.settings')->set('cache', 1)->save();

    // Load page to get the analytics.js downloaded into local cache.
    $this->drupalGet('');

    // Test if the directory and analytics.js exists.
    $this->assertTrue(file_prepare_directory($cache_path), 'Cache directory "public://google_analytics" has been found.');
    $this->assertTrue(file_exists($cache_path . '/analytics.js'), 'Cached analytics.js tracking file has been found.');
    $this->assertTrue(file_exists($cache_path . '/analytics.js.gz'), 'Cached analytics.js.gz tracking file has been found.');

    // Uninstall the module.
    $edit = [];
    $edit['uninstall[google_analytics]'] = TRUE;
    $this->drupalPostForm('admin/modules/uninstall', $edit, t('Uninstall'));
    $this->assertNoText(\Drupal::translation()->translate('Configuration deletions'), 'No configuration deletions listed on the module install confirmation page.');
    $this->drupalPostForm(NULL, NULL, t('Uninstall'));
    $this->assertText(t('The selected modules have been uninstalled.'), 'Modules status has been updated.');

    // Test if the directory and all files have been removed.
    $this->assertFalse(file_scan_directory($cache_path, '/.*/'), 'Cached JavaScript files have been removed.');
    $this->assertFalse(file_prepare_directory($cache_path), 'Cache directory "public://google_analytics" has been removed.');
  }

}
