<?php

namespace Drupal\webform\Tests;

use Drupal\webform\Element\WebformOtherBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform submission conditions (#states) validator.
 *
 * @group Webform
 */
class WebformSubmissionConditionsValidatorTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['filter', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_states_server_required', 'test_form_states_server_wizard'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create filters.
    $this->createFilters();

    $this->drupalLogin($this->rootUser);
  }

  /**
   * Tests webform submission conditions (#states) validator required.
   */
  public function testFormStatesValidatorRequired() {
    $webform = Webform::load('test_form_states_server_required');

    // Check no #states required errors.
    $this->postSubmission($webform);
    $this->assertRaw('New submission added to Test: Form API #states server-side required validation.');

    // Check required multiple dependents 'AND' and 'OR' operator.
    $edit = [
      'trigger_checkbox' => TRUE,
      'trigger_textfield' => '{value}',
      'trigger_select' => 'option',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('dependent_textfield_required_and field is required.');
    $this->assertRaw('dependent_textfield_required_or field is required.');
    $this->assertNoRaw('dependent_textfield_required_xor field is required.');

    // Check required multiple dependents 'OR' operator.
    $edit = [
      'trigger_checkbox' => TRUE,
    ];
    $this->postSubmission($webform, $edit);
    $this->assertNoRaw('dependent_textfield_required_and field is required.');
    $this->assertRaw('dependent_textfield_required_or field is required.');

    // Check required multiple dependents 'XOR' operator.
    $edit = [
      'trigger_checkbox' => TRUE,
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('dependent_textfield_required_xor field is required.');

    $edit = [
      'trigger_checkbox' => TRUE,
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('dependent_textfield_required_xor field is required.');

    // Check required checkboxes.
    $edit = [
      'checkboxes_trigger[one]' => TRUE,
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('checkboxes_dependent_required field is required.');

    // Check required text_format.
    $edit = [
      'text_format_trigger[format]' => 'full_html',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('text_format_dependent_required field is required.');

    // Check required webform_select_other select #options.
    $edit = [
      'select_other_trigger[select]' => 'one',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('select_other_dependent_required field is required.');

    // Check required webform_select_other other textfield.
    $edit = [
      'select_other_trigger[select]' => WebformOtherBase::OTHER_OPTION,
      'select_other_trigger[other]' => '{value}',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('select_other_dependent_required field is required.');

    // Check required webform_select_other_multiple select #options.
    $edit = [
      'select_other_multiple_trigger[select][]' => 'one',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('select_other_multiple_dependent_required field is required.');

    // Check required webform_email_confirm.
    $edit = [
      'email_confirm_trigger[mail_1]' => 'example@example.com',
      'email_confirm_trigger[mail_2]' => 'example@example.com',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('email_confirm_dependent_required field is required.');

    // Check required webform_likert.
    $edit = [
      'likert_trigger[q1]' => 'a1',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('likert_dependent_required field is required.');

    // Check required datelist.
    $edit = [
      'datelist_trigger[year]' => date('Y'),
      'datelist_trigger[month]' => 1,
      'datelist_trigger[day]' => 1,
      'datelist_trigger[hour]' => 1,
      'datelist_trigger[minute]' => 1,
      'datelist_trigger[second]' => 1,
      'datelist_trigger[ampm]' => 'am',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('datelist_dependent_required field is required.');

    // Check required datetime.
    $edit = [
      'datetime_trigger[date]' => date('2001-01-01'),
      'datetime_trigger[time]' => date('12:12:12'),
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('datetime_dependent_required field is required.');

    // Check required address.
    $edit = [
      'address_trigger[address]' => '{value}',
      'address_trigger[address_2]' => '{value}',
      'address_trigger[city]' => '{value}',
      'address_trigger[state_province]' => 'Alabama',
      'address_trigger[postal_code]' => '11111',
      'address_trigger[country]' => 'Afghanistan',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('address_dependent_required field is required.');

    // Check required composite.
    $edit = [
      'composite_required_trigger' => TRUE,
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('composite_required_dependent field is required.');

    // Check required composite subelements.
    $edit = [
      'composite_sub_elements_required_trigger' => 'a',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('address_a field is required.');
    $this->assertRaw('city_a field is required.');
    $this->assertRaw('state_province_a field is required.');
    $this->assertRaw('postal_code_a field is required.');
    $this->assertRaw('country_a field is required.');
    $this->assertNoRaw('address_b field is required.');
    $this->assertNoRaw('city_b field is required.');
    $this->assertNoRaw('state_province_b field is required.');
    $this->assertNoRaw('postal_code_b field is required.');
    $this->assertNoRaw('country_b field is required.');

    $edit = [
      'composite_sub_elements_required_trigger' => 'b',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertNoRaw('address_a field is required.');
    $this->assertNoRaw('city_a field is required.');
    $this->assertNoRaw('state_province_a field is required.');
    $this->assertNoRaw('postal_code_a field is required.');
    $this->assertNoRaw('country_a field is required.');
    $this->assertRaw('address_b field is required.');
    $this->assertRaw('city_b field is required.');
    $this->assertRaw('state_province_b field is required.');
    $this->assertRaw('postal_code_b field is required.');
    $this->assertRaw('country_b field is required.');
  }

  /**
   * Tests webform submission conditions (#states) validator wizard cross-page conditions.
   */
  public function testFormStatesValidatorWizard() {
    $webform = Webform::load('test_form_states_server_wizard');

    /**************************************************************************/

    // Go to default #states for page 02 with trigger-checkbox unchecked.
    $this->postSubmission($webform, [], t('Next Page >'));

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

    // Check page_02_textfield_visible is not visible.
    $this->assertNoFieldByName('page_02_textfield_visible');

    // Check page_02_textfield_invisible is visible.
    $this->assertFieldByName('page_02_textfield_invisible');

    // Check page_02_checkbox_checked is not checked.
    $this->assertRaw('<input data-drupal-selector="edit-page-02-checkbox-checked" aria-describedby="edit-page-02-checkbox-checked--description" type="checkbox" id="edit-page-02-checkbox-checked" name="page_02_checkbox_checked" value="1" class="form-checkbox" />');

    // Check page_02_checkbox_unchecked is checked.
    $this->assertRaw('<input data-drupal-selector="edit-page-02-checkbox-unchecked" aria-describedby="edit-page-02-checkbox-unchecked--description" type="checkbox" id="edit-page-02-checkbox-unchecked" name="page_02_checkbox_unchecked" value="1" checked="checked" class="form-checkbox" />');

    // Check page_02_details_expanded is not open.
    $this->assertRaw('<details data-webform-details-nosave data-webform-key="page_02_details_expanded" data-drupal-selector="edit-page-02-details-expanded" aria-describedby="edit-page-02-details-expanded--description" id="edit-page-02-details-expanded" class="js-form-wrapper form-wrapper"> ');

    // Check page_02_details_collapsed is open.
    $this->assertRaw('<details data-webform-details-nosave data-webform-key="page_02_details_collapsed" data-drupal-selector="edit-page-02-details-collapsed" aria-describedby="edit-page-02-details-collapsed--description" id="edit-page-02-details-collapsed" class="js-form-wrapper form-wrapper" open="open">');

    /**************************************************************************/

    // Go to default #states for page 02 with trigger_checkbox checked.
    $this->postSubmission($webform, ['page_01_trigger_checkbox' => TRUE], t('Next Page >'));

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
    $this->assertFieldByName('page_02_textfield_visible');

    // Check page_02_textfield_invisible is not visible.
    $this->assertNoFieldByName('page_02_textfield_invisible');

    // Check page_02_checkbox_checked is checked.
    $this->assertRaw('<input data-drupal-selector="edit-page-02-checkbox-checked" aria-describedby="edit-page-02-checkbox-checked--description" type="checkbox" id="edit-page-02-checkbox-checked" name="page_02_checkbox_checked" value="1" checked="checked" class="form-checkbox" />');

    // Check page_02_checkbox_unchecked is not checked.
    $this->assertRaw('<input data-drupal-selector="edit-page-02-checkbox-unchecked" aria-describedby="edit-page-02-checkbox-unchecked--description" type="checkbox" id="edit-page-02-checkbox-unchecked" name="page_02_checkbox_unchecked" value="1" class="form-checkbox" />');

    // Check page_02_details_expanded is open.
    $this->assertRaw('<details data-webform-details-nosave data-webform-key="page_02_details_expanded" data-drupal-selector="edit-page-02-details-expanded" aria-describedby="edit-page-02-details-expanded--description" id="edit-page-02-details-expanded" class="js-form-wrapper form-wrapper" open="open">');

    // Check page_02_details_collapsed is not open.
    $this->assertRaw('<details data-webform-details-nosave data-webform-key="page_02_details_collapsed" data-drupal-selector="edit-page-02-details-collapsed" aria-describedby="edit-page-02-details-collapsed--description" id="edit-page-02-details-collapsed" class="js-form-wrapper form-wrapper">');
  }

}
