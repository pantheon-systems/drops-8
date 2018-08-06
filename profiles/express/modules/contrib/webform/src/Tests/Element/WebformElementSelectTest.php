<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for select element.
 *
 * @group Webform
 */
class WebformElementSelectTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_select'];

  /**
   * Test select element.
   */
  public function testSelectElement() {
    // Check default empty option always included.
    $this->drupalGet('webform/test_element_select');
    $this->assertRaw('<select data-drupal-selector="edit-select-empty-option-optional" id="edit-select-empty-option-optional" name="select_empty_option_optional" class="form-select"><option value="" selected="selected">- None -</option>');
    $this->assertRaw('<select data-drupal-selector="edit-select-empty-option-optional-default-value" id="edit-select-empty-option-optional-default-value" name="select_empty_option_optional_default_value" class="form-select"><option value="">- None -</option>');
    $this->assertRaw('<select data-drupal-selector="edit-select-empty-option-required" id="edit-select-empty-option-required" name="select_empty_option_required" class="form-select required" required="required" aria-required="true"><option value="" selected="selected">- Select -</option>');

    // Disable default empty option.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('element.default_empty_option', FALSE)
      ->save();

    // Check default empty option is not always included.
    $this->drupalGet('webform/test_element_select');
    $this->assertNoRaw('<select data-drupal-selector="edit-select-empty-option-optional" id="edit-select-empty-option-optional" name="select_empty_option_optional" class="form-select"><option value="" selected="selected">- None -</option>');
    $this->assertNoRaw('<select data-drupal-selector="edit-select-empty-option-optional-default-value" id="edit-select-empty-option-optional-default-value" name="select_empty_option_optional_default_value" class="form-select"><option value="">- None -</option>');
    $this->assertRaw('<select data-drupal-selector="edit-select-empty-option-required" id="edit-select-empty-option-required" name="select_empty_option_required" class="form-select required" required="required" aria-required="true"><option value="" selected="selected">- Select -</option>');

    // Set custom empty option values
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('element.default_empty_option', TRUE)
      ->set('element.default_empty_option_required', '{required}')
      ->set('element.default_empty_option_optional', '{optional}')
      ->save();

    // Check customize empty option displayed
    $this->drupalGet('webform/test_element_select');
    $this->assertRaw('<select data-drupal-selector="edit-select-empty-option-optional" id="edit-select-empty-option-optional" name="select_empty_option_optional" class="form-select"><option value="" selected="selected">{optional}</option>');
    $this->assertRaw('<select data-drupal-selector="edit-select-empty-option-optional-default-value" id="edit-select-empty-option-optional-default-value" name="select_empty_option_optional_default_value" class="form-select"><option value="">{optional}</option>');
    $this->assertRaw('<select data-drupal-selector="edit-select-empty-option-required" id="edit-select-empty-option-required" name="select_empty_option_required" class="form-select required" required="required" aria-required="true"><option value="" selected="selected">{required}</option>');
  }

}
