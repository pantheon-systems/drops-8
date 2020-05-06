<?php

namespace Drupal\Tests\webform\Functional\Composite;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for composite elements.
 *
 * @group Webform
 */
class WebformCompositeTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform_ui'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_composite'];

  /**
   * Test composite element.
   */
  public function testComposite() {
    /* Display */

    $this->drupalGet('/webform/test_composite');

    // Check webform contact basic.
    $this->assertRaw('<fieldset data-drupal-selector="edit-contact-basic" id="edit-contact-basic--wrapper" class="webform-contact--wrapper fieldgroup form-composite webform-composite-hidden-title required js-webform-type-webform-contact webform-type-webform-contact js-form-item form-item js-form-wrapper form-wrapper">');
    $this->assertRaw('<span class="visually-hidden fieldset-legend js-form-required form-required">Contact basic</span>');
    $this->assertRaw('<label for="edit-contact-basic-name" class="js-form-required form-required">Name</label>');
    $this->assertRaw('<input data-drupal-selector="edit-contact-basic-name" type="text" id="edit-contact-basic-name" name="contact_basic[name]" value="John Smith" size="60" maxlength="255" class="form-text required" required="required" aria-required="true" />');

    // Check custom name title, description, and required.
    $this->assertRaw('<label for="edit-contact-advanced-name" class="js-form-required form-required">Custom contact name</label>');
    $this->assertRaw('<input data-drupal-selector="edit-contact-advanced-name" aria-describedby="edit-contact-advanced-name--description" type="text" id="edit-contact-advanced-name" name="contact_advanced[name]" value="John Smith" size="60" maxlength="255" class="form-text required" required="required" aria-required="true" />');
    $this->assertRaw('Custom contact name description');

    // Check custom state type and not required.
    $this->assertRaw('<label for="edit-contact-advanced-state-province">State/Province</label>');
    $this->assertRaw('<input data-drupal-selector="edit-contact-advanced-state-province" type="text" id="edit-contact-advanced-state-province" name="contact_advanced[state_province]" value="New Jersey" size="60" maxlength="255" class="form-text" />');

    // Check custom country access.
    $this->assertNoRaw('edit-contact-advanced-country');

    // Check link multiple in table.
    $this->assertRaw('<label>Link multiple</label>');
    $this->assertRaw('<th class="link_multiple-table--title webform-multiple-table--title">Link Title<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;Link Title&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;This is link title help&lt;/div&gt;"><span aria-hidden="true">?</span></span>');
    $this->assertRaw('<th class="link_multiple-table--url webform-multiple-table--url">Link URL<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;Link URL&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;This is link url help&lt;/div&gt;"><span aria-hidden="true">?</span></span>');

    /* Processing */

    // Check contact composite value.
    $this->drupalPostForm('/webform/test_composite', [], t('Submit'));
    $this->assertRaw("contact_basic:
  name: 'John Smith'
  company: Acme
  email: example@example.com
  phone: 123-456-7890
  address: '100 Main Street'
  address_2: 'PO BOX 999'
  city: 'Hill Valley'
  state_province: 'New Jersey'
  postal_code: 11111-1111
  country: 'United States'");

    // Check contact validate required composite elements.
    $edit = [
      'contact_basic[name]' => '',
    ];
    $this->drupalPostForm('/webform/test_composite', $edit, t('Submit'));
    $this->assertRaw('Name field is required.');

    /* Custom options */

    $this->drupalLogin($this->rootUser);

    // Check editing custom options are renderd.
    $this->drupalGet('/webform/test_composite');
    $this->assertRaw('<select data-drupal-selector="edit-address-custom-options-state-province" id="edit-address-custom-options-state-province" name="address_custom_options[state_province]" class="form-select"><option value="" selected="selected">- None -</option><option value="Yes">Yes</option><option value="No">No</option></select>');
    $this->assertRaw('<select data-drupal-selector="edit-address-custom-options-country" id="edit-address-custom-options-country" name="address_custom_options[country]" class="form-select"><option value="" selected="selected">- None -</option><option value="one">One</option><option value="two">Two</option><option value="three">Three</option></select>');

    // Check composite element with custom options warning message.
    $this->drupalGet('/admin/structure/webform/manage/test_composite/element/address_custom_options/edit');
    $this->assertRaw('<em>Custom options can only be updated via the <a href="' . base_path() . 'admin/structure/webform/manage/test_composite/source">YAML source</a>.</em>');

    // Save composite element with custom options.
    $this->drupalPostForm('/admin/structure/webform/manage/test_composite/element/address_custom_options/edit', [], t('Save'));

    // Check editing custom options are not removed.
    $this->drupalGet('/webform/test_composite');
    $this->assertRaw('<select data-drupal-selector="edit-address-custom-options-state-province" id="edit-address-custom-options-state-province" name="address_custom_options[state_province]" class="form-select"><option value="" selected="selected">- None -</option><option value="Yes">Yes</option><option value="No">No</option></select>');
    $this->assertRaw('<select data-drupal-selector="edit-address-custom-options-country" id="edit-address-custom-options-country" name="address_custom_options[country]" class="form-select"><option value="" selected="selected">- None -</option><option value="one">One</option><option value="two">Two</option><option value="three">Three</option></select>');

  }

}
