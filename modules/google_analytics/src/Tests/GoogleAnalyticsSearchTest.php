<?php

namespace Drupal\google_analytics\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test search functionality of Google Analytics module.
 *
 * @group Google Analytics
 */
class GoogleAnalyticsSearchTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['google_analytics', 'search', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    $permissions = [
      'access administration pages',
      'administer google analytics',
      'search content',
      'create page content',
      'edit own page content',
    ];

    // User to set up google_analytics.
    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Tests if search tracking is properly added to the page.
   */
  public function testGoogleAnalyticsSearchTracking() {
    $ua_code = 'UA-123456-1';
    $this->config('google_analytics.settings')->set('account', $ua_code)->save();

    // Check tracking code visibility.
    $this->drupalGet('');
    $this->assertRaw($ua_code, '[testGoogleAnalyticsSearch]: Tracking code is displayed for authenticated users.');

    $this->drupalGet('search/node');
    $this->assertNoRaw('ga("set", "page",', '[testGoogleAnalyticsSearch]: Custom url not set.');

    // Enable site search support.
    $this->config('google_analytics.settings')->set('track.site_search', 1)->save();

    // Search for random string.
    $search = [];
    $search['keys'] = $this->randomMachineName(8);

    // Create a node to search for.
    // Create a node.
    $edit = [];
    $edit['title[0][value]'] = 'This is a test title';
    $edit['body[0][value]'] = 'This test content contains ' . $search['keys'] . ' string.';

    // Fire a search, it's expected to get 0 results.
    $this->drupalPostForm('search/node', $search, t('Search'));
    $this->assertRaw('ga("set", "page", (window.google_analytics_search_results) ?', '[testGoogleAnalyticsSearch]: Search results tracker is displayed.');
    $this->assertRaw('window.google_analytics_search_results = 0;', '[testGoogleAnalyticsSearch]: Search yielded no results.');

    // Save the node.
    $this->drupalPostForm('node/add/page', $edit, t('Save'));
    $this->assertText(t('@type @title has been created.', ['@type' => 'Basic page', '@title' => $edit['title[0][value]']]), 'Basic page created.');

    // Index the node or it cannot found.
    $this->cronRun();

    $this->drupalPostForm('search/node', $search, t('Search'));
    $this->assertRaw('ga("set", "page", (window.google_analytics_search_results) ?', '[testGoogleAnalyticsSearch]: Search results tracker is displayed.');
    $this->assertRaw('window.google_analytics_search_results = 1;', '[testGoogleAnalyticsSearch]: One search result found.');

    $this->drupalPostForm('node/add/page', $edit, t('Save'));
    $this->assertText(t('@type @title has been created.', ['@type' => 'Basic page', '@title' => $edit['title[0][value]']]), 'Basic page created.');

    // Index the node or it cannot found.
    $this->cronRun();

    $this->drupalPostForm('search/node', $search, t('Search'));
    $this->assertRaw('ga("set", "page", (window.google_analytics_search_results) ?', '[testGoogleAnalyticsSearch]: Search results tracker is displayed.');
    $this->assertRaw('window.google_analytics_search_results = 2;', '[testGoogleAnalyticsSearch]: Two search results found.');
  }

}
