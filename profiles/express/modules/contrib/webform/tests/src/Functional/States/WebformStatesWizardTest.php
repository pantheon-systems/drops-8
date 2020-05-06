<?php

namespace Drupal\Tests\webform\Functional\States;

use Drupal\webform\Entity\Webform;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform states wizard server.
 *
 * @group Webform
 */
class WebformStatesWizardTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_states_server_wizard',
  ];

  /**
   * Tests webform submission conditions (#states) validator wizard cross-page conditions.
   */
  public function testFormStatesValidatorWizard() {
    $webform = Webform::load('test_states_server_wizard');

    /**************************************************************************/

    // Go to default #states for page 02 with trigger-checkbox unchecked.
    $this->postSubmission($webform, [], t('Next Page >'));

    $this->assertRaw("page_01_trigger_checkbox: 0
page_01_textfield_required: '{default_value}'
page_01_textfield_optional: '{default_value}'
page_01_textfield_disabled: ''
page_01_textfield_enabled: ''
page_01_textfield_visible: ''
page_01_textfield_invisible: ''
page_01_checkbox_checked: 0
page_01_checkbox_unchecked: 0
page_02_textfield_required: '{default_value}'
page_02_textfield_optional: '{default_value}'
page_02_textfield_disabled: ''
page_02_textfield_enabled: ''
page_02_textfield_visible: '{default_value}'
page_02_textfield_visible_slide: '{default_value}'
page_02_textfield_invisible: '{default_value}'
page_02_textfield_invisible_slide: '{default_value}'
page_02_checkbox_checked: 0
page_02_checkbox_unchecked: 0");

    // Check trigger-checkbox value is No.
    $this->assertRaw('<input data-drupal-selector="edit-page-01-trigger-checkbox-computed" type="hidden" name="page_01_trigger_checkbox_computed" value="No" />');

    // Check page_02_textfield_required is not required.
    $this->assertRaw('<input data-drupal-selector="edit-page-02-textfield-required" aria-describedby="edit-page-02-textfield-required--description" type="text" id="edit-page-02-textfield-required" name="page_02_textfield_required" value="{default_value}" size="60" maxlength="255" class="form-text" />');

    // Check page_02_textfield_optional is required.
    $this->assertRaw('<input data-drupal-selector="edit-page-02-textfield-optional" aria-describedby="edit-page-02-textfield-optional--description" type="text" id="edit-page-02-textfield-optional" name="page_02_textfield_optional" value="{default_value}" size="60" maxlength="255" class="form-text required" required="required" aria-required="true" />');

    // Check page_02_textfield_disabled is not disabled.
    $this->assertRaw('<input data-drupal-selector="edit-page-02-textfield-disabled" aria-describedby="edit-page-02-textfield-disabled--description" type="text" id="edit-page-02-textfield-disabled" name="page_02_textfield_disabled" value="" size="60" maxlength="255" class="form-text" />');

    // Check page_02_textfield_enabled is disabled.
    $this->assertRaw('<input data-drupal-selector="edit-page-02-textfield-enabled" aria-describedby="edit-page-02-textfield-enabled--description" disabled="disabled" type="text" id="edit-page-02-textfield-enabled" name="page_02_textfield_enabled" value="" size="60" maxlength="255" class="form-text" />');

    // Check page_02_textfield_visible is hidden via .js-webform-states-hidden.
    $this->assertRaw('<div class="js-webform-states-hidden js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-page-02-textfield-visible form-item-page-02-textfield-visible">');

    // Check page_02_textfield_visible_slide is hidden via .js-webform-states-hidden.
    $this->assertRaw('<div class="js-webform-states-hidden js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-page-02-textfield-visible-slide form-item-page-02-textfield-visible-slide">');

    // Check page_02_textfield_invisible is visible.
    $this->assertFieldByName('page_02_textfield_invisible', '{default_value}');

    // Check page_02_textfield_invisible_slide is visible.
    $this->assertFieldByName('page_02_textfield_invisible_slide', '{default_value}');

    // Check page_02_checkbox_checked is not checked.
    $this->assertRaw('<input data-drupal-selector="edit-page-02-checkbox-checked" aria-describedby="edit-page-02-checkbox-checked--description" type="checkbox" id="edit-page-02-checkbox-checked" name="page_02_checkbox_checked" value="1" class="form-checkbox" />');

    // Check page_02_checkbox_unchecked is checked.
    $this->assertRaw('<input data-drupal-selector="edit-page-02-checkbox-unchecked" aria-describedby="edit-page-02-checkbox-unchecked--description" type="checkbox" id="edit-page-02-checkbox-unchecked" name="page_02_checkbox_unchecked" value="1" checked="checked" class="form-checkbox" />');

    // Check page_02_details_expanded is not open.
    $this->assertRaw('<details data-webform-details-nosave data-webform-key="page_02_details_expanded" data-drupal-selector="edit-page-02-details-expanded" aria-describedby="edit-page-02-details-expanded--description" id="edit-page-02-details-expanded" class="js-form-wrapper form-wrapper"> ');

    // Check page_02_details_collapsed is open.
    $this->assertRaw('<details data-webform-details-nosave data-webform-key="page_02_details_collapsed" data-drupal-selector="edit-page-02-details-collapsed" aria-describedby="edit-page-02-details-collapsed--description" id="edit-page-02-details-collapsed" class="js-form-wrapper form-wrapper" open="open">');

    // Check submission data.
    $this->drupalPostForm(NULL, [], t('Submit'));
    $this->assertRaw("page_01_trigger_checkbox: 0
page_01_textfield_required: '{default_value}'
page_01_textfield_optional: '{default_value}'
page_01_textfield_disabled: ''
page_01_textfield_enabled: ''
page_01_textfield_visible: ''
page_01_textfield_invisible: ''
page_01_checkbox_checked: 0
page_01_checkbox_unchecked: 0
page_02_textfield_required: '{default_value}'
page_02_textfield_optional: '{default_value}'
page_02_textfield_disabled: ''
page_02_textfield_enabled: ''
page_02_textfield_visible: ''
page_02_textfield_visible_slide: ''
page_02_textfield_invisible: '{default_value}'
page_02_textfield_invisible_slide: '{default_value}'
page_02_checkbox_checked: 0
page_02_checkbox_unchecked: 1");

    /**************************************************************************/

    // Go to default #states for page 02 with trigger_checkbox checked.
    $this->postSubmission($webform, ['page_01_trigger_checkbox' => TRUE], t('Next Page >'));

    $this->assertRaw("page_01_trigger_checkbox: 1
page_01_textfield_required: '{default_value}'
page_01_textfield_optional: '{default_value}'
page_01_textfield_disabled: ''
page_01_textfield_enabled: ''
page_01_textfield_visible: ''
page_01_textfield_invisible: ''
page_01_checkbox_checked: 0
page_01_checkbox_unchecked: 0
page_02_textfield_required: '{default_value}'
page_02_textfield_optional: '{default_value}'
page_02_textfield_disabled: ''
page_02_textfield_enabled: ''
page_02_textfield_visible: '{default_value}'
page_02_textfield_visible_slide: '{default_value}'
page_02_textfield_invisible: '{default_value}'
page_02_textfield_invisible_slide: '{default_value}'
page_02_checkbox_checked: 0
page_02_checkbox_unchecked: 0");

    // Check trigger-checkbox value is Yes.
    $this->assertRaw('<input data-drupal-selector="edit-page-01-trigger-checkbox-computed" type="hidden" name="page_01_trigger_checkbox_computed" value="Yes" />');

    // Check page_02_textfield_required is required.
    $this->assertRaw('<input data-drupal-selector="edit-page-02-textfield-required" aria-describedby="edit-page-02-textfield-required--description" type="text" id="edit-page-02-textfield-required" name="page_02_textfield_required" value="{default_value}" size="60" maxlength="255" class="form-text required" required="required" aria-required="true" />');

    // Check page_02_textfield_optional is not required.
    $this->assertRaw('<input data-drupal-selector="edit-page-02-textfield-optional" aria-describedby="edit-page-02-textfield-optional--description" type="text" id="edit-page-02-textfield-optional" name="page_02_textfield_optional" value="{default_value}" size="60" maxlength="255" class="form-text" />');

    // Check page_02_textfield_disabled is disabled.
    $this->assertRaw('<input data-drupal-selector="edit-page-02-textfield-disabled" aria-describedby="edit-page-02-textfield-disabled--description" disabled="disabled" type="text" id="edit-page-02-textfield-disabled" name="page_02_textfield_disabled" value="" size="60" maxlength="255" class="form-text" />');

    // Check page_02_textfield_enabled is not disabled.
    $this->assertRaw('<input data-drupal-selector="edit-page-02-textfield-enabled" aria-describedby="edit-page-02-textfield-enabled--description" type="text" id="edit-page-02-textfield-enabled" name="page_02_textfield_enabled" value="" size="60" maxlength="255" class="form-text" />');

    // Check page_02_textfield_visible is visible.
    $this->assertFieldByName('page_02_textfield_visible', '{default_value}');

    // Check page_02_textfield_visible_slide is visible.
    $this->assertFieldByName('page_02_textfield_visible_slide', '{default_value}');

    // Check page_02_textfield_invisible is hidden with no default value.
    $this->assertRaw('<div class="js-webform-states-hidden js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-page-02-textfield-invisible form-item-page-02-textfield-invisible">');
    $this->assertNoFieldByName('page_02_textfield_invisible', '{default_value}');
    $this->assertFieldByName('page_02_textfield_invisible', '');

    // Check page_02_textfield_invisible_slides is hidden with no default value.
    $this->assertRaw('<div class="js-webform-states-hidden js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-page-02-textfield-invisible-slide form-item-page-02-textfield-invisible-slide">');
    $this->assertNoFieldByName('page_02_textfield_invisible_slide', '{default_value}');
    $this->assertFieldByName('page_02_textfield_invisible_slide', '');

    // Check page_02_checkbox_checked is checked.
    $this->assertRaw('<input data-drupal-selector="edit-page-02-checkbox-checked" aria-describedby="edit-page-02-checkbox-checked--description" type="checkbox" id="edit-page-02-checkbox-checked" name="page_02_checkbox_checked" value="1" checked="checked" class="form-checkbox" />');

    // Check page_02_checkbox_unchecked is not checked.
    $this->assertRaw('<input data-drupal-selector="edit-page-02-checkbox-unchecked" aria-describedby="edit-page-02-checkbox-unchecked--description" type="checkbox" id="edit-page-02-checkbox-unchecked" name="page_02_checkbox_unchecked" value="1" class="form-checkbox" />');

    // Check page_02_details_expanded is open.
    $this->assertRaw('<details data-webform-details-nosave data-webform-key="page_02_details_expanded" data-drupal-selector="edit-page-02-details-expanded" aria-describedby="edit-page-02-details-expanded--description" id="edit-page-02-details-expanded" class="js-form-wrapper form-wrapper" open="open">');

    // Check page_02_details_collapsed is not open.
    $this->assertRaw('<details data-webform-details-nosave data-webform-key="page_02_details_collapsed" data-drupal-selector="edit-page-02-details-collapsed" aria-describedby="edit-page-02-details-collapsed--description" id="edit-page-02-details-collapsed" class="js-form-wrapper form-wrapper">');

    // Check submission data.
    $this->drupalPostForm(NULL, [], t('Submit'));
    $this->assertRaw("page_01_trigger_checkbox: 1
page_01_textfield_required: '{default_value}'
page_01_textfield_optional: '{default_value}'
page_01_textfield_disabled: ''
page_01_textfield_enabled: ''
page_01_textfield_visible: ''
page_01_textfield_invisible: ''
page_01_checkbox_checked: 0
page_01_checkbox_unchecked: 0
page_02_textfield_required: '{default_value}'
page_02_textfield_optional: '{default_value}'
page_02_textfield_disabled: ''
page_02_textfield_enabled: ''
page_02_textfield_visible: '{default_value}'
page_02_textfield_visible_slide: '{default_value}'
page_02_textfield_invisible: ''
page_02_textfield_invisible_slide: ''
page_02_checkbox_checked: 1
page_02_checkbox_unchecked: 0");
  }

}
