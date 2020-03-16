<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Ensures that meta tags are rendering correctly on home page.
 *
 * @group metatag
 */
class MetatagFrontpageTest extends BrowserTestBase {

  use MetatagHelperTrait;

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
      'keywords' => 'testing,keywords',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText(t('Saved the Front page Metatag defaults.'));

    // Testing front page metatags.
    $this->drupalGet('<front>');
    foreach ($edit as $metatag => $metatag_value) {
      $xpath = $this->xpath("//meta[@name='" . $metatag . "']");
      if ($metatag == 'title') {
        $this->assertEqual(count($xpath), 0, 'Title meta tag not found.');
        $xpath = $this->xpath("//title");
        $this->assertEqual(count($xpath), 1, 'Head title tag found.');
        $value = $xpath[0]->getText();
      }
      else {
        $this->assertEqual(count($xpath), 1, 'Exactly one ' . $metatag . ' meta tag found.');
        $value = $xpath[0]->getAttribute('content');
      }
      $this->assertEqual($value, $metatag_value);
    }

    $node_path = '/node/' . $this->nodeId;
    // Testing front page metatags.
    $this->drupalGet($node_path);
    foreach ($edit as $metatag => $metatag_value) {
      $xpath = $this->xpath("//meta[@name='" . $metatag . "']");
      if ($metatag == 'title') {
        $this->assertEqual(count($xpath), 0, 'Title meta tag not found.');
        $xpath = $this->xpath("//title");
        $this->assertEqual(count($xpath), 1, 'Head title tag found.');
        $value = $xpath[0]->getText();
      }
      else {
        $this->assertEqual(count($xpath), 1, 'Exactly one ' . $metatag . ' meta tag found.');
        $value = $xpath[0]->getAttribute('content');
      }
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

    // @todo Finish this?
    $this->drupalGet('test-page');
    $this->assertResponse(200);
    foreach ($edit as $metatag => $metatag_value) {
      $xpath = $this->xpath("//meta[@name='" . $metatag . "']");
      $this->assertEqual(count($xpath), 1, 'Exactly one ' . $metatag . ' meta tag found.');
      $value = $xpath[0]->getAttribute('content');
      $this->assertEqual($value, $metatag_value);
    }
  }

  /**
   * Test front page meta tags when front page config is disabled.
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
      if ($metatag == 'title') {
        $this->assertEqual(count($xpath), 0, 'Title meta tag not found.');
        $xpath = $this->xpath("//title");
        $this->assertEqual(count($xpath), 1, 'Head title tag found.');
        $value = $xpath[0]->getText();
      }
      else {
        $this->assertEqual(count($xpath), 1, 'Exactly one ' . $metatag . ' meta tag found.');
        $value = $xpath[0]->getAttribute('content');
      }
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
      if ($metatag == 'title') {
        $this->assertEqual(count($xpath), 0, 'Title meta tag not found.');
        $xpath = $this->xpath("//title");
        $this->assertEqual(count($xpath), 1, 'Head title tag found.');
        $value = $xpath[0]->getText();
      }
      else {
        $this->assertEqual(count($xpath), 1, 'Exactly one ' . $metatag . ' meta tag found.');
        $value = $xpath[0]->getAttribute('content');
      }
      $this->assertEqual($value, $metatag_value);
    }
  }

}
