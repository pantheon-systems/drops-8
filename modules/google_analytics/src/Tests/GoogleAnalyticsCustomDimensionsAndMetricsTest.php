<?php

namespace Drupal\google_analytics\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\simpletest\WebTestBase;

/**
 * Test custom dimensions and metrics functionality of Google Analytics module.
 *
 * @group Google Analytics
 *
 * @dependencies token
 */
class GoogleAnalyticsCustomDimensionsAndMetricsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['google_analytics', 'token', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'access administration pages',
      'administer google analytics',
      'administer nodes',
      'create article content',
    ];

    // Create node type.
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    // User to set up google_analytics.
    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Tests if custom dimensions are properly added to the page.
   */
  public function testGoogleAnalyticsCustomDimensions() {
    $ua_code = 'UA-123456-3';
    $this->config('google_analytics.settings')->set('account', $ua_code)->save();
    $node = $this->drupalCreateNode([
      'type' => 'article',
    ]);

    // Basic test if the feature works.
    $google_analytics_custom_dimension = [
      1 => [
        'index' => 1,
        'value' => 'Bar 1',
      ],
      2 => [
        'index' => 2,
        'value' => 'Bar 2',
      ],
      3 => [
        'index' => 3,
        'value' => 'Bar 3',
      ],
      4 => [
        'index' => 4,
        'value' => 'Bar 4',
      ],
      5 => [
        'index' => 5,
        'value' => 'Bar 5',
      ],
    ];
    $this->config('google_analytics.settings')->set('custom.dimension', $google_analytics_custom_dimension)->save();
    $this->drupalGet('');

    foreach ($google_analytics_custom_dimension as $dimension) {
      $this->assertRaw('ga("set", ' . Json::encode('dimension' . $dimension['index']) . ', ' . Json::encode($dimension['value']) . ');', '[testGoogleAnalyticsCustomDimensionsAndMetrics]: Dimension #' . $dimension['index'] . ' is shown.');
    }

    // Test whether tokens are replaced in custom dimension values.
    $site_slogan = $this->randomMachineName(16);
    $this->config('system.site')->set('slogan', $site_slogan)->save();

    $google_analytics_custom_dimension = [
      1 => [
        'index' => 1,
        'value' => 'Value: [site:slogan]',
      ],
      2 => [
        'index' => 2,
        'value' => $this->randomMachineName(16),
      ],
      3 => [
        'index' => 3,
        'value' => '',
      ],
      // #2300701: Custom dimensions and custom metrics not outputed on zero
      // value.
      4 => [
        'index' => 4,
        'value' => '0',
      ],
      5 => [
        'index' => 5,
        'value' => '[node:type]',
      ],
      // Test google_analytics_tokens().
      6 => [
        'index' => 6,
        'value' => '[current-user:role-names]',
      ],
      7 => [
        'index' => 7,
        'value' => '[current-user:role-ids]',
      ],
    ];
    $this->config('google_analytics.settings')->set('custom.dimension', $google_analytics_custom_dimension)->save();
    $this->verbose('<pre>' . print_r($google_analytics_custom_dimension, TRUE) . '</pre>');

    // Test on frontpage.
    $this->drupalGet('');
    $this->assertRaw('ga("set", ' . Json::encode('dimension1') . ', ' . Json::encode("Value: $site_slogan") . ');', '[testGoogleAnalyticsCustomDimensionsAndMetrics]: Tokens have been replaced in dimension value.');
    $this->assertRaw('ga("set", ' . Json::encode('dimension2') . ', ' . Json::encode($google_analytics_custom_dimension['2']['value']) . ');', '[testGoogleAnalyticsCustomDimensionsAndMetrics]: Random value is shown.');
    $this->assertNoRaw('ga("set", ' . Json::encode('dimension3') . ', ' . Json::encode('') . ');', '[testGoogleAnalyticsCustomDimensionsAndMetrics]: Empty value is not shown.');
    $this->assertRaw('ga("set", ' . Json::encode('dimension4') . ', ' . Json::encode('0') . ');', '[testGoogleAnalyticsCustomDimensionsAndMetrics]: Value 0 is shown.');
    $this->assertNoRaw('ga("set", ' . Json::encode('dimension5') . ', ' . Json::encode('article') . ');', '[testGoogleAnalyticsCustomDimensionsAndMetrics]: Node tokens are shown.');
    $this->assertRaw('ga("set", ' . Json::encode('dimension6') . ', ' . Json::encode(implode(',', \Drupal::currentUser()->getRoles())) . ');', '[testGoogleAnalyticsCustomDimensionsAndMetrics]: List of roles shown.');
    $this->assertRaw('ga("set", ' . Json::encode('dimension7') . ', ' . Json::encode(implode(',', array_keys(\Drupal::currentUser()->getRoles()))) . ');', '[testGoogleAnalyticsCustomDimensionsAndMetrics]: List of role IDs shown.');

    // Test on a node.
    $this->drupalGet('node/' . $node->id());
    $this->assertText($node->getTitle());
    $this->assertRaw('ga("set", ' . Json::encode('dimension5') . ', ' . Json::encode('article') . ');', '[testGoogleAnalyticsCustomDimensionsAndMetrics]: Node tokens are shown.');
  }

  /**
   * Tests if custom metrics are properly added to the page.
   */
  public function testGoogleAnalyticsCustomMetrics() {
    $ua_code = 'UA-123456-3';
    $this->config('google_analytics.settings')->set('account', $ua_code)->save();

    // Basic test if the feature works.
    $google_analytics_custom_metric = [
      1 => [
        'index' => 1,
        'value' => '6',
      ],
      2 => [
        'index' => 2,
        'value' => '8000',
      ],
      3 => [
        'index' => 3,
        'value' => '7.8654',
      ],
      4 => [
        'index' => 4,
        'value' => '1123.4',
      ],
      5 => [
        'index' => 5,
        'value' => '5,67',
      ],
    ];

    $this->config('google_analytics.settings')->set('custom.metric', $google_analytics_custom_metric)->save();
    $this->drupalGet('');

    foreach ($google_analytics_custom_metric as $metric) {
      $this->assertRaw('ga("set", ' . Json::encode('metric' . $metric['index']) . ', ' . Json::encode((float) $metric['value']) . ');', '[testGoogleAnalyticsCustomDimensionsAndMetrics]: Metric #' . $metric['index'] . ' is shown.');
    }

    // Test whether tokens are replaced in custom metric values.
    $google_analytics_custom_metric = [
      1 => [
        'index' => 1,
        'value' => '[current-user:roles:count]',
      ],
      2 => [
        'index' => 2,
        'value' => mt_rand(),
      ],
      3 => [
        'index' => 3,
        'value' => '',
      ],
      // #2300701: Custom dimensions and custom metrics not outputed on zero
      // value.
      4 => [
        'index' => 4,
        'value' => '0',
      ],
    ];
    $this->config('google_analytics.settings')->set('custom.metric', $google_analytics_custom_metric)->save();
    $this->verbose('<pre>' . print_r($google_analytics_custom_metric, TRUE) . '</pre>');

    $this->drupalGet('');
    $this->assertRaw('ga("set", ' . Json::encode('metric1') . ', ', '[testGoogleAnalyticsCustomDimensionsAndMetrics]: Tokens have been replaced in metric value.');
    $this->assertRaw('ga("set", ' . Json::encode('metric2') . ', ' . Json::encode($google_analytics_custom_metric['2']['value']) . ');', '[testGoogleAnalyticsCustomDimensionsAndMetrics]: Random value is shown.');
    $this->assertNoRaw('ga("set", ' . Json::encode('metric3') . ', ' . Json::encode('') . ');', '[testGoogleAnalyticsCustomDimensionsAndMetrics]: Empty value is not shown.');
    $this->assertRaw('ga("set", ' . Json::encode('metric4') . ', ' . Json::encode(0) . ');', '[testGoogleAnalyticsCustomDimensionsAndMetrics]: Value 0 is shown.');
  }

  /**
   * Tests if Custom Dimensions token form validation works.
   */
  public function testGoogleAnalyticsCustomDimensionsTokenFormValidation() {
    $ua_code = 'UA-123456-1';

    // Check form validation.
    $edit['google_analytics_account'] = $ua_code;
    $edit['google_analytics_custom_dimension[indexes][1][value]'] = '[current-user:name]';
    $edit['google_analytics_custom_dimension[indexes][2][value]'] = '[current-user:edit-url]';
    $edit['google_analytics_custom_dimension[indexes][3][value]'] = '[user:name]';
    $edit['google_analytics_custom_dimension[indexes][4][value]'] = '[term:name]';
    $edit['google_analytics_custom_dimension[indexes][5][value]'] = '[term:tid]';

    $this->drupalPostForm('admin/config/system/google-analytics', $edit, t('Save configuration'));

    $this->assertRaw(t('The %element-title is using the following forbidden tokens with personal identifying information: @invalid-tokens.', ['%element-title' => t('Custom dimension value #@index', ['@index' => 1]), '@invalid-tokens' => implode(', ', ['[current-user:name]'])]));
    $this->assertRaw(t('The %element-title is using the following forbidden tokens with personal identifying information: @invalid-tokens.', ['%element-title' => t('Custom dimension value #@index', ['@index' => 2]), '@invalid-tokens' => implode(', ', ['[current-user:edit-url]'])]));
    $this->assertRaw(t('The %element-title is using the following forbidden tokens with personal identifying information: @invalid-tokens.', ['%element-title' => t('Custom dimension value #@index', ['@index' => 3]), '@invalid-tokens' => implode(', ', ['[user:name]'])]));
    // BUG #2037595
    //$this->assertNoRaw(t('The %element-title is using the following forbidden tokens with personal identifying information: @invalid-tokens.', ['%element-title' => t('Custom dimension value #@index', ['@index' => 4]), '@invalid-tokens' => implode(', ', ['[term:name]'])]));
    //$this->assertNoRaw(t('The %element-title is using the following forbidden tokens with personal identifying information: @invalid-tokens.', ['%element-title' => t('Custom dimension value #@index', ['@index' => 5]), '@invalid-tokens' => implode(', ', ['[term:tid]'])]));
  }

}
