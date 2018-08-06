<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for iCheck element.
 *
 * @group Webform
 */
class WebformElementIcheckTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_icheck'];

  /**
   * Test iCheck element.
   */
  public function testIcheckElement() {

    $this->drupalGet('webform/test_element_icheck');

    // Check custom iCheck style set to 'flat'.
    $this->assertRaw('<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-checkbox-custom form-item-checkbox-custom">');
    $this->assertRaw('<input data-webform-icheck="flat" data-drupal-selector="edit-checkbox-custom" type="checkbox" id="edit-checkbox-custom" name="checkbox_custom" value="1" class="form-checkbox" />');

    // Check default iCheck style not set.
    $this->assertRaw('<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-checkbox-default form-item-checkbox-default">');
    $this->assertRaw('<input data-drupal-selector="edit-checkbox-default" type="checkbox" id="edit-checkbox-default" name="checkbox_default" value="1" class="form-checkbox" />');

    // Check none iCheck style not set.
    $this->assertRaw('<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-checkbox-none form-item-checkbox-none">');
    $this->assertRaw('<input data-drupal-selector="edit-checkbox-none" type="checkbox" id="edit-checkbox-none" name="checkbox_none" value="1" class="form-checkbox" />');

    // Enable default icheck style.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('element.default_icheck', 'minimal')
      ->save();

    $this->drupalGet('webform/test_element_icheck');

    // Check custom iCheck style still set to 'flat'.
    $this->assertRaw('<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-checkbox-custom form-item-checkbox-custom">');
    $this->assertRaw('<input data-webform-icheck="flat" data-drupal-selector="edit-checkbox-custom" type="checkbox" id="edit-checkbox-custom" name="checkbox_custom" value="1" class="form-checkbox" />');

    // Check default iCheck style set to 'minimal'.
    $this->assertRaw('<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-checkbox-default form-item-checkbox-default">');
    $this->assertRaw('<input data-webform-icheck="minimal" data-drupal-selector="edit-checkbox-default" type="checkbox" id="edit-checkbox-default" name="checkbox_default" value="1" class="form-checkbox" />');

    // Check none iCheck style not set.
    $this->assertRaw('<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-checkbox-none form-item-checkbox-none">');
    $this->assertRaw('<input data-drupal-selector="edit-checkbox-none" type="checkbox" id="edit-checkbox-none" name="checkbox_none" value="1" class="form-checkbox" />');
  }

}
