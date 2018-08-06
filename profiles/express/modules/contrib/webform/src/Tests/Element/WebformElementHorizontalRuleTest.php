<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for horizontal rule element.
 *
 * @group Webform
 */
class WebformElementHorizontalRuleTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_horizontal_rule'];

  /**
   * Test horizontal rule element.
   */
  public function testHorizontalRule() {
    $this->drupalGet('webform/test_element_horizontal_rule');

    // Check rendering.
    $this->assertRaw('<hr data-drupal-selector="edit-horizontal-rule" id="edit-horizontal-rule" class="webform-horizontal-rule" />');
    $this->assertRaw('<hr class="webform-horizontal-rule--dotted webform-horizontal-rule" style="border-color: red" data-drupal-selector="edit-horizontal-rule-custom" id="edit-horizontal-rule-custom" />');
  }

}
