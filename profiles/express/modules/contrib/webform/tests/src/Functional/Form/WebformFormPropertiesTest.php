<?php

namespace Drupal\Tests\webform\Functional\Form;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for form properties.
 *
 * @group Webform
 */
class WebformFormPropertiesTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_properties', 'test_element_invalid'];

  /**
   * Test form properties.
   */
  public function testProperties() {
    global $base_path;

    // Check invalid elements .
    $this->drupalGet('/webform/test_element_invalid');
    $this->assertRaw('Unable to display this webform. Please contact the site administrator.');

    // Check element's root properties moved to the webform's properties.
    $this->drupalGet('/webform/test_form_properties');
    $this->assertPattern('/Form prefix<form /');
    $this->assertPattern('/<\/form>\s+Form suffix/');
    $this->assertRaw('<form class="webform-submission-form webform-submission-add-form webform-submission-test-form-properties-form webform-submission-test-form-properties-add-form test-form-properties js-webform-details-toggle webform-details-toggle" invalid="invalid" style="border: 10px solid red; padding: 1em;" data-drupal-selector="webform-submission-test-form-properties-add-form" action="https://www.google.com/search" method="get" id="webform-submission-test-form-properties-add-form" accept-charset="UTF-8">');

    // Check editing webform settings style attributes and custom properties
    // updates the element's root properties.
    $this->drupalLogin($this->rootUser);
    $edit = [
      'attributes[class][select][]' => ['form--inline clearfix', '_other_'],
      'attributes[class][other]' => 'test-form-properties',
      'attributes[style]' => 'border: 10px solid green; padding: 1em;',
      'attributes[attributes]' => '',
      'method' => '',
      'action' => '',
      'custom' => "'suffix': 'Form suffix TEST'
'prefix': 'Form prefix TEST'",
    ];
    $this->drupalPostForm('/admin/structure/webform/manage/test_form_properties/settings/form', $edit, t('Save'));
    $this->drupalGet('/webform/test_form_properties');
    $this->assertPattern('/Form prefix TEST<form /');
    $this->assertPattern('/<\/form>\s+Form suffix TEST/');
    $this->assertRaw('<form class="webform-submission-form webform-submission-add-form webform-submission-test-form-properties-form webform-submission-test-form-properties-add-form form--inline clearfix test-form-properties js-webform-details-toggle webform-details-toggle" style="border: 10px solid green; padding: 1em;" data-drupal-selector="webform-submission-test-form-properties-add-form" action="' . $base_path . 'webform/test_form_properties" method="post" id="webform-submission-test-form-properties-add-form" accept-charset="UTF-8">');
  }

}
