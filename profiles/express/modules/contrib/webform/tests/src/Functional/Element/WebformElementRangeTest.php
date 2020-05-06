<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for range element.
 *
 * @group Webform
 */
class WebformElementRangeTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_range'];

  /**
   * Test range element.
   */
  public function testRating() {
    $this->drupalGet('/webform/test_element_range');

    // Check basic range element.
    $this->assertRaw('<input data-drupal-selector="edit-range" type="range" id="edit-range" name="range" value="" step="1" min="0" max="100" class="form-range" />');

    // Check advanced range element.
    $this->assertRaw('<label for="edit-range-advanced">range_advanced</label>');
    $this->assertRaw('<span class="field-prefix">-100</span>');
    $this->assertRaw('<input style="width: 400px" data-drupal-selector="edit-range-advanced" type="range" id="edit-range-advanced" name="range_advanced" value="" step="1" min="-100" max="100" class="form-range" />');
    $this->assertRaw('<span class="field-suffix">100</span>');

    // Check output above range element.
    $this->assertRaw('<output for="range_output_above" data-display="above"></output>');

    // Check output below with custom range element.
    $this->assertRaw('<output style="background-color: yellow" for="range_output_below" data-display="below" data-field-prefix="$" data-field-suffix=".00"></output>');

    // Check output left range element.
    $this->assertRaw('<span class="field-prefix"><div class="js-form-item form-item js-form-type-number form-type-number js-form-item-range-output-left__output form-item-range-output-left__output form-no-label">');
    $this->assertRaw('<label for="range_output_left__output" class="visually-hidden">range_output_left</label>');
    $this->assertRaw('<input style="background-color: yellow;width:6em" type="number" id="range_output_left__output" step="100" min="0" max="10000" class="form-number" />');

    // Check output right range element.
    $this->assertRaw('<span class="field-suffix"><span class="webform-range-output-delimiter"></span><div class="js-form-item form-item js-form-type-number form-type-number js-form-item-range-output-disabled__output form-item-range-output-disabled__output form-no-label form-disabled">');
    $this->assertRaw('<label for="range_output_right__output" class="visually-hidden">range_output_right</label>');
    $this->assertRaw('<input style="width:4em" type="number" id="range_output_right__output" step="1" min="0" max="100" class="form-number" />');

    // Check processing.
    $this->drupalPostForm('/webform/test_element_range', [], t('Submit'));
    $this->assertRaw("range: '50'
range_advanced: '0'
range_output_above: '50'
range_output_below: '50'
range_output_right: '50'
range_output_left: '5000'
range_output_disabled: ''");
  }

}
