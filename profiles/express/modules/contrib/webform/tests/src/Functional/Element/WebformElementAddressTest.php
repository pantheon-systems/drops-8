<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform address element.
 *
 * @group Webform
 */
class WebformElementAddressTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'address', 'node'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_address'];

  /**
   * Tests address element.
   */
  public function testAddress() {
    $this->drupalLogin($this->rootUser);

    $webform = Webform::load('test_element_address');

    /**************************************************************************/
    // Rendering.
    /**************************************************************************/

    $this->drupalGet('/webform/test_element_address');

    // Check basic fieldset wrapper.
    $this->assertRaw('<fieldset data-drupal-selector="edit-address" id="edit-address--wrapper" class="address--wrapper fieldgroup form-composite webform-composite-hidden-title js-webform-type-address webform-type-address js-form-item form-item js-form-wrapper form-wrapper">');
    $this->assertRaw('<span class="visually-hidden fieldset-legend">address_basic</span>');

    // Check advanced fieldset, legend, help, and description.
    $this->assertRaw('<fieldset data-drupal-selector="edit-address-advanced" aria-describedby="edit-address-advanced--wrapper--description" id="edit-address-advanced--wrapper" class="address--wrapper fieldgroup form-composite webform-composite-visible-title webform-element-help-container--title webform-element-help-container--title-after js-webform-type-address webform-type-address js-form-item form-item js-form-wrapper form-wrapper">');
    $this->assertRaw('<span class="fieldset-legend">address_advanced<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;address_advanced&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;This is help text&lt;/div&gt;"><span aria-hidden="true">?</span></span>');
    $this->assertRaw('<div class="description"><div id="edit-address-advanced--wrapper--description" class="webform-element-description">This is a description</div>');

    /**************************************************************************/
    // Processing.
    /**************************************************************************/

    // Check submitted value.
    $sid = $this->postSubmission($webform);
    $this->assertRaw("address:
  country_code: US
  langcode: en
  given_name: John
  family_name: Smith
  organization: 'Google Inc.'
  address_line1: '1098 Alta Ave'
  address_line2: ''
  locality: 'Mountain View'
  administrative_area: CA
  postal_code: '94043'
  additional_name: null
  sorting_code: null
  dependent_locality: null
address_advanced:
  country_code: US
  langcode: en
  address_line1: '1098 Alta Ave'
  address_line2: ''
  locality: 'Mountain View'
  administrative_area: CA
  postal_code: '94043'
  given_name: null
  additional_name: null
  family_name: null
  organization: null
  sorting_code: null
  dependent_locality: null
address_none: null
address_multiple:
  - country_code: US
    langcode: en
    given_name: John
    family_name: Smith
    organization: 'Google Inc.'
    address_line1: '1098 Alta Ave'
    address_line2: ''
    locality: 'Mountain View'
    administrative_area: CA
    postal_code: '94043'");

    // Check text formatting.
    $this->drupalGet("/admin/structure/webform/manage/test_element_address/submission/$sid/text");
    $this->assertRaw('address_basic:
John Smith
Google Inc.
1098 Alta Ave
Mountain View, CA 94043
United States

address_advanced:
1098 Alta Ave
Mountain View, CA 94043
United States

address_none:
{Empty}

address_multiple:
- John Smith
  Google Inc.
  1098 Alta Ave
  Mountain View, CA 94043
  United States');

    /**************************************************************************/
    // Schema.
    /**************************************************************************/

    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'address',
      'type' => 'address',
    ]);
    $schema = $field_storage->getSchema();

    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');
    /** @var \Drupal\webform\Plugin\WebformElement\Address $element_plugin */
    $element_plugin = $element_manager->getElementInstance(['#type' => 'address']);

    // Get webform address element plugin.
    $element = [];
    $element_plugin->initializeCompositeElements($element);

    // Check composite elements against address schema.
    $composite_elements = $element['#webform_composite_elements'];
    $diff_composite_elements = array_diff_key($composite_elements, $schema['columns']);
    $this->debug($diff_composite_elements);
    $this->assert(empty($diff_composite_elements));

    // Check composite elements maxlength against address schema.
    foreach ($schema['columns'] as $column_name => $column) {
      $this->assertEqual($composite_elements[$column_name]['#maxlength'], $column['length']);
    }
  }

}
