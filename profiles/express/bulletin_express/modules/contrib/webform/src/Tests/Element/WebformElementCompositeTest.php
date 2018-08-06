<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for composite element.
 *
 * @group Webform
 */
class WebformElementCompositeTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_composite'];

  /**
   * Test composite element.
   */
  public function testCompositeElement() {

    /* Display */

    $this->drupalGet('webform/test_element_composite');

    // Check webform contact basic.
    $this->assertRaw('<fieldset data-drupal-selector="edit-contact-basic" id="edit-contact-basic--wrapper" class="fieldgroup form-composite js-webform-contact webform-contact required js-form-item form-item js-form-wrapper form-wrapper" required="required" aria-required="true">');
    $this->assertNoRaw('<span class="fieldset-legend js-form-required form-required">Contact basic</span>');
    $this->assertRaw('<label for="edit-contact-basic-name" class="js-form-required form-required">Name</label>');
    $this->assertRaw('<input data-drupal-selector="edit-contact-basic-name" type="text" id="edit-contact-basic-name" name="contact_basic[name]" value="John Smith" size="60" maxlength="128" class="form-text required" required="required" aria-required="true" />');

    // Check custom name title, description, and required.
    $this->assertRaw('<label for="edit-contact-advanced-name" class="js-form-required form-required">Custom contact name</label>');
    $this->assertRaw('<input data-drupal-selector="edit-contact-advanced-name" aria-describedby="edit-contact-advanced-name--description" type="text" id="edit-contact-advanced-name" name="contact_advanced[name]" value="John Smith" size="60" maxlength="128" class="form-text required" required="required" aria-required="true" />');
    $this->assertRaw('Custom contact name description');

    // Check custom state type and not required.
    $this->assertRaw('<label for="edit-contact-advanced-state-province">State/Province</label>');
    $this->assertRaw('<input data-drupal-selector="edit-contact-advanced-state-province" type="text" id="edit-contact-advanced-state-province" name="contact_advanced[state_province]" value="New Jersey" size="60" maxlength="128" class="form-text" />');

    // Check custom country access.
    $this->assertNoRaw('edit-contact-advanced-country');

    // Check credit card.
    $this->assertRaw('<fieldset data-drupal-selector="edit-creditcard-basic" id="edit-creditcard-basic--wrapper" class="fieldgroup form-composite js-webform-creditcard webform-creditcard js-form-item form-item js-form-wrapper form-wrapper">');
    $this->assertRaw('<span class="visually-hidden fieldset-legend">Credit Card</span>');
    $this->assertNoRaw('<span class="fieldset-legend">Credit Card</span>');
    $this->assertRaw('The credit card element is experimental and insecure because it stores submitted information as plain text.');
    $this->assertRaw('<label for="edit-creditcard-basic-name">Name on Card</label>');
    $this->assertRaw('<input data-drupal-selector="edit-creditcard-basic-name" type="text" id="edit-creditcard-basic-name" name="creditcard_basic[name]" value="John Smith" size="60" maxlength="128" class="form-text" />');

    /* Processing */

    // Check contact composite value.
    $this->drupalPostForm('webform/test_element_composite', [], t('Submit'));
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
    $this->drupalPostForm('webform/test_element_composite', $edit, t('Submit'));
    $this->assertRaw('Name field is required.');

    // Check creditcard composite value.
    $this->drupalPostForm('webform/test_element_composite', [], t('Submit'));
    $this->assertRaw("creditcard_basic:
  name: 'John Smith'
  type: VI
  number: '4111111111111111'
  civ: '111'
  expiration_month: '1'
  expiration_year: '2025'");
  }

}
