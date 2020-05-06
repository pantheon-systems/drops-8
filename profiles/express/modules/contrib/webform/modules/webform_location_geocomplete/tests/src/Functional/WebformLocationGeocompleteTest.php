<?php

namespace Drupal\Tests\webform_location_geocomplete\Functional;

use Drupal\Tests\webform\Functional\Element\WebformElementBrowserTestBase;

/**
 * Tests for location geocomplete element.
 *
 * @group Webform
 */
class WebformLocationGeocompleteTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform_location_geocomplete', 'webform_location_geocomplete_test'];

  /**
   * Test locatin geocomplete element.
   */
  public function testLocationGeocompleElement() {
    $this->drupalGet('/webform/test_element_loc_geocomplete');

    // Check basic address.
    $this->assertRaw('<input class="webform-location-geocomplete form-text" data-drupal-selector="edit-location-default-value" type="text" id="edit-location-default-value" name="location_default[value]" value="" size="60" maxlength="255" />');
    $this->assertRaw('<input data-webform-location-geocomplete-attribute="lat" data-drupal-selector="edit-location-default-lat" type="hidden" name="location_default[lat]" value="" />');
    $this->assertRaw('<input data-webform-location-geocomplete-attribute="lng" data-drupal-selector="edit-location-default-lng" type="hidden" name="location_default[lng]" value="" />');
    $this->assertRaw('<input data-webform-location-geocomplete-attribute="location" data-drupal-selector="edit-location-default-location" type="hidden" name="location_default[location]" value="" />');
    $this->assertRaw('<input data-webform-location-geocomplete-attribute="formatted_address" data-drupal-selector="edit-location-default-formatted-address" type="hidden" name="location_default[formatted_address]" value="" />');
    $this->assertRaw('<input data-webform-location-geocomplete-attribute="street_address" data-drupal-selector="edit-location-default-street-address" type="hidden" name="location_default[street_address]" value="" />');
    $this->assertRaw('<input data-webform-location-geocomplete-attribute="street_number" data-drupal-selector="edit-location-default-street-number" type="hidden" name="location_default[street_number]" value="" />');
    $this->assertRaw('<input data-webform-location-geocomplete-attribute="subpremise" data-drupal-selector="edit-location-default-subpremise" type="hidden" name="location_default[subpremise]" value="" />');
    $this->assertRaw('<input data-webform-location-geocomplete-attribute="postal_code" data-drupal-selector="edit-location-default-postal-code" type="hidden" name="location_default[postal_code]" value="" />');
    $this->assertRaw('<input data-webform-location-geocomplete-attribute="locality" data-drupal-selector="edit-location-default-locality" type="hidden" name="location_default[locality]" value="" />');
    $this->assertRaw('<input data-webform-location-geocomplete-attribute="sublocality" data-drupal-selector="edit-location-default-sublocality" type="hidden" name="location_default[sublocality]" value="" />');
    $this->assertRaw('<input data-webform-location-geocomplete-attribute="administrative_area_level_1" data-drupal-selector="edit-location-default-administrative-area-level-1" type="hidden" name="location_default[administrative_area_level_1]" value="" />');
    $this->assertRaw('<input data-webform-location-geocomplete-attribute="country" data-drupal-selector="edit-location-default-country" type="hidden" name="location_default[country]" value="" />');
    $this->assertRaw('<input data-webform-location-geocomplete-attribute="country_short" data-drupal-selector="edit-location-default-country-short" type="hidden" name="location_default[country_short]" value="" />');
  }

}
