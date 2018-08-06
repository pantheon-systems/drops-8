<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verify that enabling Devel don't cause the site to blow up.
 *
 * @group metatag
 */
class EnsureDevelWorks extends BrowserTestBase {

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

    // Use the custom route to verify the site works.
    'metatag_test_custom_route',

    // The modules to test.
    'devel',
  ];

  /**
   * Load the custom route, make sure something is output.
   */
  public function testCustomRoute() {
    $this->drupalGet('metatag_test_custom_route');
    $this->assertResponse(200);
    $this->assertText('Hello world!');
  }

  /**
   * Make sure that the system still works when some example content exists.
   */
  public function testNode() {
    $node = $this->createContentTypeNode();
    $this->drupalGet($node->toUrl());
    $this->assertResponse(200);
  }

}
