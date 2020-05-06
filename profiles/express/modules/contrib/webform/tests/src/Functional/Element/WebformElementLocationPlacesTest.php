<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;

/**
 * Tests for location (Algolia) places element.
 *
 * @group Webform
 */
class WebformElementLocationPlacesTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_loc_places'];

  /**
   * Test location (Algolia) places element.
   */
  public function testLocationPlaces() {
    $webform = Webform::load('test_element_loc_places');

    $this->drupalGet('/webform/test_element_loc_places');

    // Check hidden attributes.
    $this->assertRaw('<input class="webform-location-places form-text" data-drupal-selector="edit-location-default-value" type="text" id="edit-location-default-value" name="location_default[value]" value="" size="60" maxlength="255" placeholder="Enter a location" />');
    $this->assertRaw('<input data-webform-location-places-attribute="lat" data-drupal-selector="edit-location-default-lat" type="hidden" name="location_default[lat]" value="" />');
    $this->assertRaw('<input data-webform-location-places-attribute="lng" data-drupal-selector="edit-location-default-lng" type="hidden" name="location_default[lng]" value="" />');
    $this->assertRaw('<input data-webform-location-places-attribute="name" data-drupal-selector="edit-location-default-name" type="hidden" name="location_default[name]" value="" />');
    $this->assertRaw('<input data-webform-location-places-attribute="city" data-drupal-selector="edit-location-default-city" type="hidden" name="location_default[city]" value="" />');
    $this->assertRaw('<input data-webform-location-places-attribute="country" data-drupal-selector="edit-location-default-country" type="hidden" name="location_default[country]" value="" />');
    $this->assertRaw('<input data-webform-location-places-attribute="country_code" data-drupal-selector="edit-location-default-country-code" type="hidden" name="location_default[country_code]" value="" />');
    $this->assertRaw('<input data-webform-location-places-attribute="administrative" data-drupal-selector="edit-location-default-administrative" type="hidden" name="location_default[administrative]" value="" />');
    $this->assertRaw('<input data-webform-location-places-attribute="county" data-drupal-selector="edit-location-default-county" type="hidden" name="location_default[county]" value="" />');
    $this->assertRaw('<input data-webform-location-places-attribute="suburb" data-drupal-selector="edit-location-default-suburb" type="hidden" name="location_default[suburb]" value="" />');
    $this->assertRaw('<input data-webform-location-places-attribute="postcode" data-drupal-selector="edit-location-default-postcode" type="hidden" name="location_default[postcode]" value="" />');

    // Check visible attributes.
    $this->assertRaw('<input class="webform-location-places form-text" data-drupal-selector="edit-location-attributes-value" type="text" id="edit-location-attributes-value" name="location_attributes[value]" value="" size="60" maxlength="255" />');
    $this->assertRaw('<input data-webform-location-places-attribute="lat" data-drupal-selector="edit-location-attributes-lat" type="text" id="edit-location-attributes-lat" name="location_attributes[lat]" value="" size="60" maxlength="255" class="form-text" />');

    // Check invalid validation.
    $edit = [
      'location_attributes_required[value]' => 'test',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('The location_attributes_required is not valid.');

    // Check valid validation with lat(itude).
    $edit = [
      'location_attributes_required[value]' => 'test',
      'location_attributes_required[lat]' => 1,
    ];
    $this->postSubmission($webform, $edit);
    $this->assertNoRaw('The location_attributes_required is not valid.');

    // Check application id and API key is missing.
    $this->assertNoRaw('"app_id"');
    $this->assertNoRaw('"api_key"');

    // Set application id and API key.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('element.default_algolia_places_app_id', '{default_algolia_places_app_id}')
      ->set('element.default_algolia_places_api_key', '{default_algolia_places_api_key}')
      ->save();

    // Check application id and API key is set.
    $this->drupalGet('/webform/test_element_loc_places');
    $this->assertRaw('"app_id"');
    $this->assertRaw('"api_key"');
    $this->assertRaw('"webform":{"location":{"places":{"app_id":"{default_algolia_places_app_id}","api_key":"{default_algolia_places_api_key}"}}}');
  }

}
