<?php

namespace Drupal\Tests\video_filter\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test basic functionality of Video Filter module.
 *
 * @group Video Filter
 */
class Basics extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // Modules for core functionality.
    'node',
    'filter',

    // This module.
    'video_filter',
  ];

  /**
   * Verify the front page works.
   */
  public function testFrontpage() {
    // Load the front page.
    $this->drupalGet('<front>');
    $this->assertResponse(200);

    // With nothing else configured the front page just has a login form.
    $this->assertText('Enter your Drupal username.');
  }

}
