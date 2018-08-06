<?php

namespace Drupal\google_analytics\Tests;

use Drupal\Component\Utility\Html;
use Drupal\simpletest\WebTestBase;

/**
 * Test php filter functionality of Google Analytics module.
 *
 * @group Google Analytics
 *
 * @dependencies php
 */
class GoogleAnalyticsPhpFilterTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['google_analytics', 'php'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Administrator with all permissions.
    $permissions_admin_user = [
      'access administration pages',
      'administer google analytics',
      'use PHP for google analytics tracking visibility',
    ];
    $this->admin_user = $this->drupalCreateUser($permissions_admin_user);

    // Administrator who cannot configure tracking visibility with PHP.
    $permissions_delegated_admin_user = [
      'access administration pages',
      'administer google analytics',
    ];
    $this->delegated_admin_user = $this->drupalCreateUser($permissions_delegated_admin_user);
  }

  /**
   * Tests if PHP module integration works.
   */
  public function testGoogleAnalyticsPhpFilter() {
    $ua_code = 'UA-123456-1';
    $this->drupalLogin($this->admin_user);

    $edit = [];
    $edit['google_analytics_account'] = $ua_code;
    $edit['google_analytics_visibility_request_path_mode'] = 2;
    $edit['google_analytics_visibility_request_path_pages'] = '<?php return 0; ?>';
    $this->drupalPostForm('admin/config/system/google-analytics', $edit, t('Save configuration'));

    // Compare saved setting with posted setting.
    $google_analytics_pages = \Drupal::config('google_analytics.settings')->get('visibility.request_path_pages');
    $this->assertEqual('<?php return 0; ?>', $google_analytics_pages, '[testGoogleAnalyticsPhpFilter]: PHP code snippet is intact.');

    // Check tracking code visibility.
    $this->config('google_analytics.settings')->set('visibility.request_path_pages', '<?php return TRUE; ?>')->save();
    $this->drupalGet('');
    $this->assertRaw('https://www.google-analytics.com/analytics.js', '[testGoogleAnalyticsPhpFilter]: Tracking is displayed on frontpage page.');
    $this->drupalGet('admin');
    $this->assertRaw('https://www.google-analytics.com/analytics.js', '[testGoogleAnalyticsPhpFilter]: Tracking is displayed on admin page.');

    $this->config('google_analytics.settings')->set('visibility.request_path_pages', '<?php return FALSE; ?>')->save();
    $this->drupalGet('');
    $this->assertNoRaw('https://www.google-analytics.com/analytics.js', '[testGoogleAnalyticsPhpFilter]: Tracking is not displayed on frontpage page.');

    // Test administration form.
    $this->config('google_analytics.settings')->set('visibility.request_path_pages', '<?php return TRUE; ?>')->save();
    $this->drupalGet('admin/config/system/google-analytics');
    $this->assertRaw(t('Pages on which this PHP code returns <code>TRUE</code> (experts only)'), '[testGoogleAnalyticsPhpFilter]: Permission to administer PHP for tracking visibility.');
    $this->assertRaw(Html::escape('<?php return TRUE; ?>'), '[testGoogleAnalyticsPhpFilter]: PHP code snippted is displayed.');

    // Login the delegated user and check if fields are visible.
    $this->drupalLogin($this->delegated_admin_user);
    $this->drupalGet('admin/config/system/google-analytics');
    $this->assertNoRaw(t('Pages on which this PHP code returns <code>TRUE</code> (experts only)'), '[testGoogleAnalyticsPhpFilter]: No permission to administer PHP for tracking visibility.');
    $this->assertNoRaw(Html::escape('<?php return TRUE; ?>'), '[testGoogleAnalyticsPhpFilter]: No permission to view PHP code snippted.');

    // Set a different value and verify that this is still the same after the
    // post.
    $this->config('google_analytics.settings')->set('visibility.request_path_pages', '<?php return 0; ?>')->save();

    $edit = [];
    $edit['google_analytics_account'] = $ua_code;
    $this->drupalPostForm('admin/config/system/google-analytics', $edit, t('Save configuration'));

    // Compare saved setting with posted setting.
    $google_analytics_visibility_pages = \Drupal::config('google_analytics.settings')->get('visibility.request_path_mode');
    $google_analytics_pages = \Drupal::config('google_analytics.settings')->get('visibility.request_path_pages');
    $this->assertEqual(2, $google_analytics_visibility_pages, '[testGoogleAnalyticsPhpFilter]: Pages on which this PHP code returns TRUE is selected.');
    $this->assertEqual('<?php return 0; ?>', $google_analytics_pages, '[testGoogleAnalyticsPhpFilter]: PHP code snippet is intact.');
  }

}
