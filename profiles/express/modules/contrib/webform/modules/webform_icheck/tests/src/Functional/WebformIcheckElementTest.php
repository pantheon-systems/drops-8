<?php

namespace Drupal\Tests\webform_icheck\Functional;

use Drupal\Tests\webform\Functional\Element\WebformElementBrowserTestBase;

/**
 * Tests for iCheck element.
 *
 * @group Webform
 */
class WebformIcheckElementTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform_icheck', 'webform_icheck_test'];

  /**
   * Test iCheck element.
   */
  public function testIcheckElement() {
    $this->drupalGet('/webform/test_element_icheck');

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
    /** @var \Drupal\webform\WebformThirdPartySettingsManagerInterface $third_party_settings_manager */
    $third_party_settings_manager = \Drupal::service('webform.third_party_settings_manager');
    $third_party_settings_manager->setThirdPartySetting('webform_icheck', 'default_icheck', 'minimal');

    $this->drupalGet('/webform/test_element_icheck');

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
