<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for webform element radios.
 *
 * @group Webform
 */
class WebformElementRadiosTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_radios'];

  /**
   * Tests radios element.
   */
  public function testElementRadios() {
    $this->drupalLogin($this->rootUser);

    $this->drupalGet('/webform/test_element_radios');

    // Check radios with description display.
    $this->assertRaw('<input data-drupal-selector="edit-radios-description-one" aria-describedby="edit-radios-description-one--description" type="radio" id="edit-radios-description-one" name="radios_description" value="one" class="form-radio" />');
    $this->assertRaw('<label for="edit-radios-description-one" class="option">One</label>');
    $this->assertRaw('<div id="edit-radios-description-one--description" class="webform-element-description">This is a description</div>');

    // Check radios with help text display.
    $this->assertRaw('<input data-drupal-selector="edit-radios-help-one" type="radio" id="edit-radios-help-one" name="radios_help" value="one" class="form-radio" />');
    $this->assertRaw('<label for="edit-radios-help-one" class="option">One<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;One&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;This is a description&lt;/div&gt;"><span aria-hidden="true">?</span></span>');

    // Check radios displayed as buttons.
    $this->assertRaw('<div id="edit-radios-buttons" class="js-webform-radios webform-options-display-buttons form-radios"><div class="webform-options-display-buttons-wrapper">');
    $this->assertRaw('<input data-drupal-selector="edit-radios-buttons-yes" class="visually-hidden form-radio" type="radio" id="edit-radios-buttons-yes" name="radios_buttons" value="Yes" />');
    $this->assertRaw('<label class="webform-options-display-buttons-label option" for="edit-radios-buttons-yes">Yes</label>');

    // Check radios displayed as buttons with description.
    $this->assertRaw('<label class="webform-options-display-buttons-label option" for="edit-radios-buttons-description-one"><div class="webform-options-display-buttons-title">One</div><div class="webform-options-display-buttons-description description">This is a description</div></label>');

    // Check options (custom) properties wrapper attributes.
    $this->assertRaw('<div data-custom="custom wrapper data" style="border: red 1px solid" class="one-custom-wrapper-class js-form-item form-item js-form-type-radio form-type-radio js-form-item-radios-options-properties form-item-radios-options-properties">');

    // Check options (custom) properties label attributes.
    $this->assertRaw('<label data-custom="custom label data" style="border: blue 1px solid" class="one-custom-label-class option" for="edit-radios-options-properties-two">Two</label>');

    // Check options (custom) properties attributes.
    $this->assertRaw('<input data-drupal-selector="edit-radios-options-properties-two" data-custom="custom input data" style="border: yellow 1px solid" class="one-custom-class form-radio" aria-describedby="edit-radios-options-properties-two--description" type="radio" id="edit-radios-options-properties-two" name="radios_options_properties" value="two" />');

    // Check other options (custom) properties attributes.
    $this->assertRaw('<input data-drupal-selector="edit-radios-other-options-properties-radios-one" disabled="disabled" type="radio" id="edit-radios-other-options-properties-radios-one" name="radios_other_options_properties[radios]" value="one" class="form-radio" />');

    // Check radios results does not include description.
    $edit = [
      'radios_required' => 'Yes',
      'radios_required_conditional_trigger' => FALSE,
      'buttons_required_conditional_trigger' => FALSE,
      'radios_description' => 'one',
      'radios_help' => 'two',
    ];
    $this->drupalPostForm('/webform/test_element_radios', $edit, t('Preview'));
    $this->assertPattern('#<label>radios_description</label>\s+One#');
    $this->assertPattern('#<label>radios_help</label>\s+Two#');
  }

}
