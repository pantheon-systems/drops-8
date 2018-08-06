<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Example of webform browser test.
 *
 * @group webform_browser
 */
class WebformExampleFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['webform'];

  /**
   * Test get.
   */
  public function testGet() {
    $this->drupalGet('/webform/contact');
    $this->assertSession()->responseContains('Contact');
  }

}
