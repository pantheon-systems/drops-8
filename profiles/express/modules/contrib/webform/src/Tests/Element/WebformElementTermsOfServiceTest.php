<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform terms of service element.
 *
 * @group Webform
 */
class WebformElementTermsOfServiceTest extends WebformTestBase {

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
    $this->drupalGet('webform/test_element_terms_of_service');
    $this->assertRaw('<div data-webform-terms-of-service-type="modal" class="js-form-type-webform-terms-of-service js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-terms-of-service-default form-item-terms-of-service-default">');
    $this->assertRaw('<input data-drupal-selector="edit-terms-of-service-default" type="checkbox" id="edit-terms-of-service-default" name="terms_of_service_default" value="1" class="form-checkbox" />');
    $this->assertRaw('<label for="edit-terms-of-service-default" class="option">I agree to the <a>terms of service</a>. (default)</label>');
    $this->assertRaw('<div id="edit-terms-of-service-default--description" class="description">');
    $this->assertRaw('<div class="webform-terms-of-service-details js-hide"><div class="webform-terms-of-service-details--title">terms_of_service_default</div><div class="webform-terms-of-service-details--content">These are the terms of service.</div></div>');

    // Check preview.
    $edit = [
      'terms_of_service_default' => TRUE,
      'terms_of_service_modal' => TRUE,
      'terms_of_service_slideout' => TRUE,
    ];
    $this->drupalPostForm('webform/test_element_terms_of_service', $edit, t('Preview'));
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
