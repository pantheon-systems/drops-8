<?php

namespace Drupal\metatag\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Ensures that metatags are rendering correctly on home page.
 *
 * @group metatag
 */
class MetatagFrontpageTest extends WebTestBase {

  // Use the helper functions from the Functional trait. This is pretty safe but
  // remember to rewrite all of these WebTestBase tests using BrowserTestBase
  // before the next millenium.
  use \Drupal\Tests\metatag\Functional\MetatagHelperTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'token',
    'metatag',
    'node',
    'system',
    'test_page_test',
  ];

  /**
   * The path to a node that is created for testing.
   *
   * @var string
   */
  protected $nodeId;

  /**
   * Setup basic environment.
   */
  protected function setUp() {
    parent::setUp();

    // Login user 1.
    $this->loginUser1();

    // Create content type.
    $this->drupalCreateContentType(['type' => 'page', 'display_submitted' => FALSE]);
    $this->nodeId = $this->drupalCreateNode(
      [
        'title' => $this->randomMachineName(8),
        'promote' => 1,
      ])->id();

    $this->config('system.site')->set('page.front', '/node/' . $this->nodeId)->save();
  }

  /**
   * The front page config is enabled, its meta tags should be used.
   */
  public function testFrontPageMetatagsEnabledConfig() {
    // Add something to the front page config.
    $this->drupalGet('admin/config/search/metatag/front');
    $this->assertResponse(200);
    $edit = [
      'title' => 'Test title',
      'description' => 'Test description',
      'keywords' => 'testing,keywords'
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText(t('Saved the Front page Metatag defaults.'));

    // Testing front page metatags.
    $this->drupalGet('<front>');
    foreach ($edit as $metatag => $metatag_value) {
      $xpath = $this->xpath("//meta[@name='" . $metatag . "']");
      $this->assertEqual(count($xpath), 1, 'Exactly one ' . $metatag . ' meta tag found.');
      $value = (string) $xpath[0]['content'];
      $this->assertEqual($value, $metatag_value);
    }

    $node_path = '/node/' . $this->nodeId;
    // Testing front page metatags.
    $this->drupalGet($node_path);
    foreach ($edit as $metatag => $metatag_value) {
      $xpath = $this->xpath("//meta[@name='" . $metatag . "']");
      $this->assertEqual(count($xpath), 1, 'Exactly one ' . $metatag . ' meta tag found.');
      $value = (string) $xpath[0]['content'];
      $this->assertEqual($value, $metatag_value);
    }

    // Change the front page to a valid custom route.
    $site_edit = [
      'site_frontpage' => '/test-page',
    ];
    $this->drupalGet('admin/config/system/site-information');
    $this->assertResponse(200);
    $this->drupalPostForm(NULL, $site_edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'), 'The front page path has been saved.');
    return;

    $this->drupalGet('test-page');
    $this->assertResponse(200);
    foreach ($edit as $metatag => $metatag_value) {
      $xpath = $this->xpath("//meta[@name='" . $metatag . "']");
      $this->assertEqual(count($xpath), 1, 'Exactly one ' . $metatag . ' meta tag found.');
      $value = (string) $xpath[0]['content'];
      $this->assertEqual($value, $metatag_value);
    }
  }

  /**
   * Test front page metatags when front page config is disabled.
   */
  public function testFrontPageMetatagDisabledConfig() {
    // Disable front page metatag, enable node metatag & check.
    $this->drupalGet('admin/config/search/metatag/front/delete');
    $this->assertResponse(200);
    $this->drupalPostForm(NULL, [], t('Delete'));
    $this->assertResponse(200);
    $this->assertText(t('Deleted Front page defaults.'));

    // Update the Metatag Node defaults.
    $this->drupalGet('admin/config/search/metatag/node');
    $this->assertResponse(200);
    $edit = [
      'title' => 'Test title for a node.',
      'description' => 'Test description for a node.',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('Saved the Content Metatag defaults.');
    $this->drupalGet('<front>');
    foreach ($edit as $metatag => $metatag_value) {
      $xpath = $this->xpath("//meta[@name='" . $metatag . "']");
      $this->assertEqual(count($xpath), 1, 'Exactly one ' . $metatag . ' meta tag found.');
      $value = (string) $xpath[0]['content'];
      $this->assertEqual($value, $metatag_value);
    }

    // Change the front page to a valid path.
    $this->drupalGet('admin/config/system/site-information');
    $this->assertResponse(200);
    $edit = [
      'site_frontpage' => '/test-page',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'), 'The front page path has been saved.');

    // Front page is custom route.
    // Update the Metatag Node global.
    $this->drupalGet('admin/config/search/metatag/global');
    $this->assertResponse(200);
    $edit = [
      'title' => 'Test title.',
      'description' => 'Test description.',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('Saved the Global Metatag defaults.');

    // Test Metatags.
    $this->drupalGet('test-page');
    $this->assertResponse(200);
    foreach ($edit as $metatag => $metatag_value) {
      $xpath = $this->xpath("//meta[@name='" . $metatag . "']");
      $this->assertEqual(count($xpath), 1, 'Exactly one ' . $metatag . ' meta tag found.');
      $value = (string) $xpath[0]['content'];
      $this->assertEqual($value, $metatag_value);
    }
  }

}
