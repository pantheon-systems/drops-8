<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform checkboxes element.
 *
 * @group Webform
 */
class WebformElementCheckboxesTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_checkboxes'];

  /**
   * Tests checkbox and checkboxes element.
   */
  public function testCheckboxes() {
    $webform = Webform::load('test_element_checkboxes');

    $this->drupalGet('/webform/test_element_checkboxes');

    // Check checkboxes displayed as buttons.
    $this->assertRaw('<div id="edit-checkboxes-buttons" class="js-webform-checkboxes webform-options-display-buttons form-checkboxes"><div class="webform-options-display-buttons-wrapper">');
    $this->assertRaw('<input data-drupal-selector="edit-checkboxes-buttons-yes" class="visually-hidden form-checkbox" type="checkbox" id="edit-checkboxes-buttons-yes" name="checkboxes_buttons[Yes]" value="Yes" />');
    $this->assertRaw('<label class="webform-options-display-buttons-label option" for="edit-checkboxes-buttons-yes">Yes</label>');

    // Check checkboxes displayed as buttons with description.
    $this->assertRaw('<label class="webform-options-display-buttons-label option" for="edit-checkboxes-buttons-description-one"><div class="webform-options-display-buttons-title">One</div><div class="webform-options-display-buttons-description description">This is a description</div></label>');

    // Check options (custom) properties wrapper attributes.
    $this->assertRaw('<div data-custom="custom wrapper data" style="border: red 1px solid" class="one-custom-wrapper-class js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-checkboxes-options-properties-two form-item-checkboxes-options-properties-two">');

    // Check options (custom) properties label attributes.
    $this->assertRaw('<label data-custom="custom label data" style="border: blue 1px solid" class="one-custom-label-class option" for="edit-checkboxes-options-properties-two">Two</label>');

    // Check options (custom) properties attributes.
    $this->assertRaw('<input data-drupal-selector="edit-checkboxes-options-properties-two" data-custom="custom input data" style="border: yellow 1px solid" class="one-custom-class form-checkbox" aria-describedby="edit-checkboxes-options-properties-two--description" type="checkbox" id="edit-checkboxes-options-properties-two" name="checkboxes_options_properties[two]" value="two" />');

    // Check other options (custom) properties attributes.
    $this->assertRaw('<input data-drupal-selector="edit-checkboxes-other-options-properties-checkboxes-one" disabled="disabled" type="checkbox" id="edit-checkboxes-other-options-properties-checkboxes-one" name="checkboxes_other_options_properties[checkboxes][one]" value="one" class="form-checkbox" />');

    // Check exclude empty is not visible.
    $edit = [
      'checkboxes_required_conditions[Yes]' => TRUE,
      'checkboxes_other_required_conditions[checkboxes][Yes]' => TRUE,
    ];
    $this->postSubmission($webform, $edit, t('Preview'));
    $this->assertNoRaw('<label>checkbox_exclude_empty</label>');

    // Uncheck #exclude_empty.
    $webform->setElementProperties('checkbox_exclude_empty', ['#type' => 'checkbox', '#title' => 'checkbox_exclude_empty']);
    $webform->save();

    // Check exclude empty is visible.
    $edit = [
      'checkboxes_required_conditions[Yes]' => TRUE,
      'checkboxes_other_required_conditions[checkboxes][Yes]' => TRUE,
    ];
    $this->postSubmission($webform, $edit, t('Preview'));
    $this->assertRaw('<label>checkbox_exclude_empty</label>');
  }

}
