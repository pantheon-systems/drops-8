<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for scale element.
 *
 * @group Webform
 */
class WebformElementScaleTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_scale'];

  /**
   * Test scale element.
   */
  public function testRating() {
    $this->drupalGet('/webform/test_element_scale');

    // Check basic scale element.
    $this->assertRaw('<div class="webform-scale webform-scale-circle webform-scale-medium webform-scale-1-to-5">');
    $this->assertRaw('<input data-drupal-selector="edit-scale-1" class="webform-scale-1 visually-hidden form-radio" type="radio" id="edit-scale-1" name="scale" value="1" />');

    // Check scale with text element.
    $this->assertRaw('<div class="webform-scale webform-scale-circle webform-scale-medium webform-scale-0-to-10">');
    $this->assertRaw('<input data-drupal-selector="edit-scale-text-0" class="webform-scale-0 visually-hidden form-radio" type="radio" id="edit-scale-text-0" name="scale_text" value="0" />');
    $this->assertRaw('<div class="webform-scale-text webform-scale-text-below"><div class="webform-scale-text-min">0 = disagree</div><div class="webform-scale-text-max">agree = 10</div></div></div></div>');

    // Check processing.
    $edit = [
      'scale' => 1,
      'scale_text' => 2,
    ];
    $this->drupalPostForm('/webform/test_element_scale', $edit, t('Submit'));
    $this->assertRaw("scale: '1'
scale_text: '2'
scale_text_above: null
scale_small: null
scale_medium: null
scale_large: null
scale_square: null
scale_flexbox: null");
  }

}
