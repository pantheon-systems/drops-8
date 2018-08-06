<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for composite custom element.
 *
 * @group Webform
 */
class WebformElementCompositeCustomTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_composite_custom'];

  /**
   * Test composite custom element.
   */
  public function testCompositeCustom() {

    /* Display */

    $this->drupalGet('webform/test_element_composite_custom');

    // Check basic custom composite.
    $this->assertRaw('<label for="edit-webform-composite-basic">webform_composite_basic</label>');
    $this->assertRaw('<div id="webform_composite_basic_table" class="webform-multiple-table">');
    $this->assertRaw('<th class="webform_composite_basic-table--handle webform-multiple-table--handle"></th>');
    $this->assertRaw('<th class="webform_composite_basic-table--first_name webform-multiple-table--first_name">First name</th>');
    $this->assertRaw('<th class="webform_composite_basic-table--last_name webform-multiple-table--last_name">Last name</th>');
    $this->assertRaw('<th class="webform_composite_basic-table--weight webform-multiple-table--weight">Weight</th>');

    // Check advanced custom composite.
    $this->assertRaw('<span class="field-suffix"> yrs. old</span>');

    /* Processing */

    // Check contact composite value.
    $this->drupalPostForm('webform/test_element_composite_custom', [], t('Submit'));
    $this->assertRaw("webform_composite_basic:
  - first_name: John
    last_name: Smith
webform_composite_advanced:
  - first_name: John
    last_name: Smith
    gender: Male
    martial_status: Single
    employment_status: Unemployed
    age: '20'");
  }

}
