<?php

namespace Drupal\libraries_ui\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the libraries_ui module.
 */
class LibraresUiControllerTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => "libraries_ui LibraresUiController's controller functionality",
      'description' => 'Test Unit for module libraries_ui and controller LibraresUiController.',
      'group' => 'Other',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests libraries_ui functionality.
   */
  public function testLibraresUiController() {
    // Check that the basic functions of module libraries_ui.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via App Console.');
  }

}
