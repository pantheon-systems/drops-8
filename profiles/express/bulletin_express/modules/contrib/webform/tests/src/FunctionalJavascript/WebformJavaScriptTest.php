<?php

namespace Drupal\Tests\webform\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests webform JavasScript.
 *
 * @group webform
 */
class WebformJavaScriptTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['webform'];

  /**
   * Tests JavaScript.
   */
  public function testJavaScript() {
    $this->drupalGet('webform/contact');
    $this->assertSession()->responseContains('Send message');
  }

}
