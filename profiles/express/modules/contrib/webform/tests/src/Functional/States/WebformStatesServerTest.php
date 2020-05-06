<?php

namespace Drupal\Tests\webform\Functional\States;

use Drupal\Component\Utility\Crypt;
use Drupal\webform\Element\WebformOtherBase;
use Drupal\webform\Entity\Webform;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform submission conditions (#states) validator.
 *
 * @group Webform
 */
class WebformStatesServerTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_states_crosspage',
    'test_states_server_custom',
    'test_states_server_comp',
    'test_states_server_likert',
    'test_states_server_nested',
    'test_states_server_multiple',
    'test_states_server_containers',
    'test_states_server_required',
  ];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['filter', 'webform'];

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

    /**************************************************************************/
    // required.
    /**************************************************************************/

    $webform = Webform::load('test_states_server_required');

    // Check no #states required errors.
    $this->postSubmission($webform);
    $this->assertRaw('New submission added to Test: Form API #states server-side required validation.');

    /**************************************************************************/
    // multiple_triggers.
    /**************************************************************************/

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

    /**************************************************************************/
    // multiple_dependents.
    /**************************************************************************/

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

    /**************************************************************************/
    // required_hidden_trigger.
    /**************************************************************************/

    $edit = [
      'required_hidden_trigger' => TRUE,
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('required_hidden_dependent_required field is required.');

    /**************************************************************************/
    // minlength_hidden_trigger.
    /**************************************************************************/

    $edit = [
      'minlength_hidden_trigger' => TRUE,
    ];
    $this->postSubmission($webform, $edit);
    $this->assertNoRaw('<em class="placeholder">minlength_hidden_dependent</em> cannot be less than <em class="placeholder">5</em> characters');

    $edit = [
      'minlength_hidden_trigger' => TRUE,
      'minlength_hidden_dependent' => 'X',
    ];
    $this->postSubmission($webform, $edit);
    // $this->assertRaw('<em class="placeholder">minlength_hidden_dependent</em> cannot be less than <em class="placeholder">5</em> characters');

    /**************************************************************************/
    // checkboxes_trigger.
    /**************************************************************************/

    // Check required checkboxes.
    $edit = [
      'checkboxes_trigger[one]' => TRUE,
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('checkboxes_dependent_required field is required.');

    /**************************************************************************/
    // checkboxes_other_trigger.
    /**************************************************************************/

    // Check required checkboxes other checkbox.
    $edit = [
      'checkboxes_other_trigger[checkboxes][one]' => TRUE,
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('checkboxes_other_dependent_required field is required.');

    // Check required checkboxes other text field.
    $edit = [
      'checkboxes_other_trigger[checkboxes][_other_]' => TRUE,
      'checkboxes_other_trigger[other]' => 'filled',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('checkboxes_other_dependent_required field is required.');

    /**************************************************************************/
    // text_format_trigger.
    /**************************************************************************/

    // Check required text_format.
    $edit = [
      'text_format_trigger[format]' => 'full_html',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('text_format_dependent_required field is required.');

    /**************************************************************************/
    // select_other_trigger.
    /**************************************************************************/

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

    /**************************************************************************/
    // select_other_multiple_trigger.
    /**************************************************************************/

    // Check required webform_select_other_multiple select #options.
    $edit = [
      'select_other_multiple_trigger[select][]' => 'one',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('select_other_multiple_dependent_required field is required.');

    /**************************************************************************/
    // select_values_trigger.
    /**************************************************************************/

    // Check required select_values_trigger select option 'one'.
    $edit = [
      'select_values_trigger' => 'one',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('select_values_trigger_dependent_required field is required.');

    // Check required select_values_trigger select option 'two'.
    $edit = [
      'select_values_trigger' => 'two',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('select_values_trigger_dependent_required field is required.');

    /**************************************************************************/
    // email_confirm_trigger.
    /**************************************************************************/

    // Check required webform_email_confirm.
    $edit = [
      'email_confirm_trigger[mail_1]' => 'example@example.com',
      'email_confirm_trigger[mail_2]' => 'example@example.com',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('email_confirm_dependent_required field is required.');

    /**************************************************************************/
    // likert_trigger.
    /**************************************************************************/

    // Check required webform_likert.
    $edit = [
      'likert_trigger[q1]' => 'a1',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('likert_dependent_required field is required.');

    /**************************************************************************/
    // datelist_trigger.
    /**************************************************************************/

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

    /**************************************************************************/
    // datetime_trigger.
    /**************************************************************************/

    // Check required datetime.
    $edit = [
      'datetime_trigger[date]' => date('2001-01-01'),
      'datetime_trigger[time]' => date('12:12:12'),
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('datetime_dependent_required field is required.');

    /**************************************************************************/
    // currency_trigger.
    /**************************************************************************/

    // Check required currency input mask.
    $edit = [
      'currency_trigger' => TRUE,
      'currency_dependent_required' => '$ 0.00',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('currency_dependent_required field is required.');

    /**************************************************************************/
    // address_trigger.
    /**************************************************************************/

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

    /**************************************************************************/
    // composite_required.
    /**************************************************************************/

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

    /**************************************************************************/
    // custom.
    /**************************************************************************/

    $webform = Webform::load('test_states_server_custom');

    // Check no #states required errors.
    $this->postSubmission($webform);
    $this->assertRaw('New submission added to Test: Form API #states custom pattern, less, greater, and between condition validation.');

    $edit = [
      'trigger_pattern' => 'abc',
      'trigger_not_pattern' => 'ABC',
      'trigger_less' => 1,
      'trigger_greater' => 11,
    ];
    $this->postSubmission($webform, $edit);
    $this->assertNoRaw('New submission added to Test: Form API #states custom pattern, less, greater, and between condition validation.');
    $this->assertRaw('dependent_pattern field is required.');
    $this->assertRaw('dependent_not_pattern field is required.');
    $this->assertRaw('dependent_less field is required.');
    $this->assertRaw('dependent_greater field is required.');

    $edit = [
      'trigger_between' => 9,
    ];
    $this->postSubmission($webform, $edit);
    $this->assertNoRaw('dependent_between field is required.');

    $edit = [
      'trigger_between' => 21,
    ];
    $this->postSubmission($webform, $edit);
    $this->assertNoRaw('dependent_between field is required.');

    /**************************************************************************/
    // multiple element.
    /**************************************************************************/

    $webform = Webform::load('test_states_server_multiple');

    $edit = [
      'trigger_required' => TRUE,
    ];
    $this->postSubmission($webform, $edit);

    // Check multiple error.
    $this->assertRaw('textfield_multiple field is required.');

    /**************************************************************************/
    // composite element.
    /**************************************************************************/

    $webform = Webform::load('test_states_server_comp');

    $edit = [
      'webform_name_trigger' => TRUE,
      'webform_name_multiple_trigger' => TRUE,
      'webform_name_multiple_header_trigger' => TRUE,
      'webform_name_nested_trigger' => TRUE,
    ];
    $this->postSubmission($webform, $edit);

    // Check basic composite.
    $this->assertRaw('First field is required.');
    $this->assertRaw('<input data-drupal-selector="edit-webform-name-first" type="text" id="edit-webform-name-first" name="webform_name[first]" value="" size="60" maxlength="255" class="form-text error" aria-invalid="true" data-drupal-states="{&quot;required&quot;:{&quot;.webform-submission-test-states-server-comp-add-form :input[name=\u0022webform_name_trigger\u0022]&quot;:{&quot;checked&quot;:true}}}" />');

    // Check multiple composite with custom error.
    $this->assertRaw("Custom error message for &#039;last&#039; element.");
    $this->assertRaw('<input data-drupal-selector="edit-webform-name-multiple-items-0-item-last" type="text" id="edit-webform-name-multiple-items-0-item-last" name="webform_name_multiple[items][0][_item_][last]" value="" size="60" maxlength="255" class="form-text error" aria-invalid="true" data-drupal-states="{&quot;required&quot;:{&quot;:input[name=\u0022webform_name_multiple_trigger\u0022]&quot;:{&quot;checked&quot;:true}}}" />');

    // Check multiple table composite.
    $this->assertRaw('Last field is required.');
    $this->assertRaw('<input data-drupal-selector="edit-webform-name-multiple-header-items-0-last" type="text" id="edit-webform-name-multiple-header-items-0-last" name="webform_name_multiple_header[items][0][last]" value="" size="60" maxlength="255" class="form-text error" aria-invalid="true" data-drupal-states="{&quot;required&quot;:{&quot;.webform-submission-test-states-server-comp-add-form :input[name=\u0022webform_name_multiple_header_trigger\u0022]&quot;:{&quot;checked&quot;:true}}}" />');

    // Check single nested composite.
    $this->assertRaw('webform_name_nested_first field is required.');
    $this->assertRaw('webform_name_nested_last field is required.');
    $this->assertRaw(' <input data-drupal-selector="edit-webform-name-nested-last" type="text" id="edit-webform-name-nested-last" name="webform_name_nested[last]" value="" size="60" maxlength="255" class="form-text error" aria-invalid="true" data-drupal-states="{&quot;required&quot;:{&quot;.webform-submission-test-states-server-comp-add-form :input[name=\u0022webform_name_nested_trigger\u0022]&quot;:{&quot;checked&quot;:true}}}" />');

    /**************************************************************************/
    // likert element.
    /**************************************************************************/

    $webform = Webform::load('test_states_server_likert');

    // Check required error.
    $this->postSubmission($webform, ['trigger_likert' => TRUE]);
    $this->assertRaw('q1 field is required.');
    $this->assertRaw('q2 field is required.');

    // Check required error.
    $this->postSubmission($webform, [
      'trigger_likert' => TRUE,
      'dependent_likert[q1]' => 'a1',
      'dependent_likert[q2]' => 'a2',
    ]);
    $this->assertNoRaw('q1 field is required.');
    $this->assertNoRaw('q2 field is required.');

    /**************************************************************************/
    // nested containers.
    /**************************************************************************/

    $webform = Webform::load('test_states_server_containers');

    // Check sub elements.
    $this->drupalGet('/webform/test_states_server_containers');
    $this->assertRaw('<input data-drupal-selector="edit-visible-textfield" type="text" id="edit-visible-textfield" name="visible_textfield" value="" size="60" maxlength="255" class="form-text" data-drupal-states="{&quot;required&quot;:{&quot;.webform-submission-test-states-server-containers-add-form :input[name=\u0022visible_trigger\u0022]&quot;:{&quot;checked&quot;:true}}}" />');
    $this->assertRaw('<input data-drupal-selector="edit-visible-custom-textfield" type="text" id="edit-visible-custom-textfield" name="visible_custom_textfield" value="" size="60" maxlength="255" class="form-text" data-drupal-states="{&quot;required&quot;:{&quot;.webform-submission-test-states-server-containers-add-form :input[name=\u0022visible_trigger\u0022]&quot;:{&quot;checked&quot;:true},&quot;.webform-submission-test-states-server-containers-add-form :input[name=\u0022visible_textfield\u0022]&quot;:{&quot;filled&quot;:true}}}" />');
    $this->assertRaw('<input data-drupal-selector="edit-visible-slide-textfield" type="text" id="edit-visible-slide-textfield" name="visible_slide_textfield" value="" size="60" maxlength="255" class="form-text" data-drupal-states="{&quot;required&quot;:{&quot;.webform-submission-test-states-server-containers-add-form :input[name=\u0022visible_trigger\u0022]&quot;:{&quot;checked&quot;:true}}}" />');
    $this->assertRaw('<input data-drupal-selector="edit-visible-slide-custom-textfield" type="text" id="edit-visible-slide-custom-textfield" name="visible_slide_custom_textfield" value="" size="60" maxlength="255" class="form-text" data-drupal-states="{&quot;required&quot;:{&quot;.webform-submission-test-states-server-containers-add-form :input[name=\u0022visible_trigger\u0022]&quot;:{&quot;checked&quot;:true},&quot;.webform-submission-test-states-server-containers-add-form :input[name=\u0022visible_slide_textfield\u0022]&quot;:{&quot;filled&quot;:true}}}" />');
    $this->assertRaw('<input data-drupal-selector="edit-visible-composite-items-0-textfield" type="text" id="edit-visible-composite-items-0-textfield" name="visible_composite[items][0][textfield]" value="" size="60" maxlength="255" class="form-text" data-drupal-states="{&quot;required&quot;:{&quot;.webform-submission-test-states-server-containers-add-form :input[name=\u0022visible_trigger\u0022]&quot;:{&quot;checked&quot;:true}}}" />');

    // Check nested element is required.
    $edit = [
      'visible_trigger' => TRUE,
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('visible_textfield field is required.');
    $this->assertNoRaw('visible_custom_textfield field is required.');
    $this->assertRaw('visible_slide_textfield field is required.');
    $this->assertNoRaw('visible_slide_custom_textfield field is required.');
    $this->assertRaw('textfield field is required.');
    $this->assertRaw('select_other field is required.');

    // Check nested element is not required.
    $edit = [];
    $this->postSubmission($webform, $edit);
    $this->assertNoRaw('visible_textfield field is required.');
    $this->assertNoRaw('visible_custom_textfield field is required.');
    $this->assertNoRaw('visible_slide_textfield field is required.');
    $this->assertNoRaw('visible_slide_custom_textfield field is required.');
    $this->assertNoRaw('textfield field is required.');
    $this->assertNoRaw('select_other field is required.');

    // Check custom states element validation.
    $edit = [
      'visible_trigger' => TRUE,
      'visible_textfield' => '{value}',
      'visible_slide_textfield' => '{value}',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('visible_custom_textfield field is required.');
    $this->assertRaw('visible_slide_custom_textfield field is required.');

    /**************************************************************************/
    // nested conditions.
    /**************************************************************************/

    $webform = Webform::load('test_states_server_nested');

    // Check a and b sets target required page 1.
    $edit = ['a' => TRUE, 'b' => TRUE, 'c' => FALSE];
    $this->drupalPostForm('/webform/test_states_server_nested', $edit, t('Next Page >'));
    $this->assertRaw('page_1_target: [a and b] or c = required field is required.');

    // Check c sets target required page 1.
    $edit = ['a' => FALSE, 'b' => TRUE, 'c' => TRUE];
    $this->drupalPostForm('/webform/test_states_server_nested', $edit, t('Next Page >'));
    $this->assertRaw('page_1_target: [a and b] or c = required field is required.');

    // Check none sets target not required page 1.
    $edit = ['a' => FALSE, 'b' => FALSE, 'c' => FALSE];
    $this->drupalPostForm('/webform/test_states_server_nested', $edit, t('Next Page >'));
    $this->assertNoRaw('page_1_target: [a and b] or c = required field is required.');

    // Check none sets target not required page 2.
    $this->assertRaw('<label for="edit-page-2-target">page_2_target: [a and b] or c = required</label>');
    $this->assertRaw('<input data-drupal-selector="edit-page-2-target" type="text" id="edit-page-2-target" name="page_2_target" value="" size="60" maxlength="255" class="form-text" />');

    // Check a and b sets target required page 2.
    $edit = ['a' => TRUE, 'b' => TRUE, 'c' => FALSE, 'page_1_target' => '{value}'];
    $this->drupalPostForm('/webform/test_states_server_nested', $edit, t('Next Page >'));
    $this->assertNoRaw('<input data-drupal-selector="edit-page-2-target" type="text" id="edit-page-2-target" name="page_2_target" value="" size="60" maxlength="255" class="form-text" />');
    $this->assertRaw('<label for="edit-page-2-target" class="js-form-required form-required">page_2_target: [a and b] or c = required</label>');
    $this->assertRaw('<input data-drupal-selector="edit-page-2-target" type="text" id="edit-page-2-target" name="page_2_target" value="" size="60" maxlength="255" class="form-text required" required="required" aria-required="true" />');

    /**************************************************************************/
    // test_states_crosspage.
    /**************************************************************************/

    $webform = Webform::load('test_states_crosspage');

    $trigger_1_name = 'webform_states_' . Crypt::hashBase64('.webform-submission-test-states-crosspage-add-form :input[name="trigger_1"]');
    $trigger_2_name = 'webform_states_' . Crypt::hashBase64('.webform-submission-test-states-crosspage-add-form :input[name="trigger_2"]');

    // Check cross page states attribute and input on page 1.
    $this->drupalGet('/webform/test_states_crosspage');
    $this->assertRaw(':input[name=\u0022' . $trigger_2_name . '\u0022]');
    $this->assertFieldByName($trigger_2_name);

    // Check cross page states attribute and input on page 2.
    $this->postSubmission($webform, ['trigger_1' => TRUE], t('Next Page >'));
    $this->assertRaw(':input[name=\u0022' . $trigger_1_name . '\u0022]');
    $this->assertFieldByName($trigger_1_name);
  }

}
