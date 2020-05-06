<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for webform terms of service element.
 *
 * @group Webform
 */
class WebformElementTermsOfServiceTest extends WebformElementBrowserTestBase {

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
  protected static $testWebforms = ['test_element_terms_of_service'];

  /**
   * Tests TermsOfService element.
   */
  public function testTermsOfService() {
    // Check rendering.
    $this->drupalGet('/webform/test_element_terms_of_service');

    // Check modal.
    $this->assertRaw('<div data-webform-terms-of-service-type="modal" class="form-type-webform-terms-of-service js-form-type-webform-terms-of-service js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-terms-of-service-default form-item-terms-of-service-default">');
    $this->assertRaw('<input data-drupal-selector="edit-terms-of-service-default" type="checkbox" id="edit-terms-of-service-default" name="terms_of_service_default" value class="form-checkbox required" required="required" aria-required="true" />');
    $this->assertRaw('<label for="edit-terms-of-service-default" class="option js-form-required form-required">I agree to the <a role="button" href="#terms">terms of service</a>. (default)</label>');
    $this->assertRaw('<div id="edit-terms-of-service-default--description" class="webform-element-description">');
    $this->assertRaw('<div id="webform-terms-of-service-terms_of_service_default--description" class="webform-terms-of-service-details js-hide">');
    $this->assertRaw('<div class="webform-terms-of-service-details--title">terms_of_service_default</div>');
    $this->assertRaw('<div class="webform-terms-of-service-details--content">These are the terms of service.</div>');

    // Check slideout.
    $this->assertRaw('<label for="edit-terms-of-service-slideout" class="option">I agree to the <a role="button" href="#terms">terms of service</a>. (slideout)</label>');

    // Check validation.
    $this->drupalPostForm('/webform/test_element_terms_of_service', [], t('Preview'));
    $this->assertRaw('I agree to the {terms of service}. (default) field is required.');

    // Check preview.
    $edit = [
      'terms_of_service_default' => TRUE,
      'terms_of_service_modal' => TRUE,
      'terms_of_service_slideout' => TRUE,
    ];
    $this->drupalPostForm('/webform/test_element_terms_of_service', $edit, t('Preview'));
    $this->assertRaw('I agree to the terms of service. (default)');
    $this->assertRaw('I agree to the terms of service. (modal)');
    $this->assertRaw('I agree to the terms of service. (slideout)');

    // Check default title and auto incremented key.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/structure/webform/manage/test_element_terms_of_service/element/add/webform_terms_of_service');
    $this->assertFieldByName('key', 'terms_of_service_01');
    $this->assertFieldByName('properties[title]', 'I agree to the {terms of service}.');
  }

}
