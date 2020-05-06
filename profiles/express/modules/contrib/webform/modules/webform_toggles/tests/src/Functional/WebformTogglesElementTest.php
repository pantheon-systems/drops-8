<?php

namespace Drupal\Tests\webform_toggles\Functional;

use Drupal\Tests\webform\Functional\Element\WebformElementBrowserTestBase;

/**
 * Tests for toggles element.
 *
 * @group Webform
 */
class WebformTogglesElementTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform_toggles', 'webform_toggles_test'];

  /**
   * Test toggles element.
   */
  public function testTogglesElement() {
    $this->drupalGet('/webform/test_element_toggles');

    // Check basic toggle.
    $this->assertRaw('<div class="js-form-item form-item js-form-type-webform-toggle form-type-webform-toggle js-form-item-toggle-basic form-item-toggle-basic">');
    $this->assertRaw('<input data-drupal-selector="edit-toggle-basic" type="checkbox" id="edit-toggle-basic" name="toggle_basic" value="1" class="form-checkbox" />');
    $this->assertRaw('<div class="js-webform-toggle webform-toggle toggle toggle-medium toggle-light" data-toggle-height="24" data-toggle-width="48" data-toggle-text-on="" data-toggle-text-off=""></div>');
    $this->assertRaw('<label for="edit-toggle-basic" class="option">Basic toggle</label>');

    // Check advanced toggle.
    $this->assertRaw('<div class="js-form-item form-item js-form-type-webform-toggle form-type-webform-toggle js-form-item-toggle-advanced form-item-toggle-advanced">');
    $this->assertRaw('<label for="edit-toggle-advanced">Advanced toggle</label>');
    $this->assertRaw('<input data-drupal-selector="edit-toggle-advanced" type="checkbox" id="edit-toggle-advanced" name="toggle_advanced" value="1" class="form-checkbox" />');
    $this->assertRaw('<div class="js-webform-toggle webform-toggle toggle toggle-large toggle-iphone" data-toggle-height="36" data-toggle-width="108" data-toggle-text-on="Yes" data-toggle-text-off="No"></div>');

    // Check basic toggles.
    $this->assertRaw('<fieldset data-drupal-selector="edit-toggles-basic" id="edit-toggles-basic--wrapper" class="webform-toggles--wrapper fieldgroup form-composite webform-composite-visible-title js-webform-type-webform-toggles webform-type-webform-toggles js-form-item form-item js-form-wrapper form-wrapper">');
    $this->assertRaw('<span class="fieldset-legend">Basic toggles</span>');
    $this->assertRaw('<div id="edit-toggles-basic" class="js-webform-webform-toggles form-checkboxes"><div class="js-form-item form-item js-form-type-webform-toggle form-type-webform-toggle js-form-item-toggles-basic-one form-item-toggles-basic-one">');
    $this->assertRaw('<input data-drupal-selector="edit-toggles-basic-one" type="checkbox" id="edit-toggles-basic-one" name="toggles_basic[one]" value="one" class="form-checkbox" /><div class="js-webform-toggle webform-toggle toggle toggle-medium toggle-light" data-toggle-height="24" data-toggle-width="48" data-toggle-text-on="" data-toggle-text-off=""></div>');
    $this->assertRaw('<label for="edit-toggles-basic-one" class="option">One</label>');

    // Check advanced toggles.
    $this->assertRaw('<fieldset data-drupal-selector="edit-toggles-advanced" id="edit-toggles-advanced--wrapper" class="webform-toggles--wrapper fieldgroup form-composite webform-composite-visible-title js-webform-type-webform-toggles webform-type-webform-toggles js-form-item form-item js-form-wrapper form-wrapper">');
    $this->assertRaw('<span class="fieldset-legend">Advanced toggles</span>');
    $this->assertRaw('<div id="edit-toggles-advanced" class="js-webform-webform-toggles form-checkboxes"><div class="js-form-item form-item js-form-type-webform-toggle form-type-webform-toggle js-form-item-toggles-advanced-one form-item-toggles-advanced-one">');
    $this->assertRaw('<input data-drupal-selector="edit-toggles-advanced-one" type="checkbox" id="edit-toggles-advanced-one" name="toggles_advanced[one]" value="one" class="form-checkbox" /><div class="js-webform-toggle webform-toggle toggle toggle-large toggle-iphone" data-toggle-height="36" data-toggle-width="108" data-toggle-text-on="Yes" data-toggle-text-off="No"></div>');
  }

}
