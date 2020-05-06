<?php

namespace Drupal\webform_location_geocomplete\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformLocationBase;

/**
 * Provides a webform element for a location geocomplete element.
 *
 * @FormElement("webform_location_geocomplete")
 */
class WebformLocationGeocomplete extends WebformLocationBase {

  /**
   * {@inheritdoc}
   */
  protected static $name = 'geocomplete';

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + [
      '#api_key' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getLocationAttributes() {
    return [
      'lat' => t('Latitude'),
      'lng' => t('Longitude'),
      'location' => t('Location'),
      'formatted_address' => t('Formatted Address'),
      'street_address' => t('Street Address'),
      'street_number' => t('Street Number'),
      'subpremise' => t('Unit'),
      'postal_code' => t('Postal Code'),
      'locality' => t('Locality'),
      'sublocality' => t('City'),
      'administrative_area_level_1' => t('State/Province'),
      'country' => t('Country'),
      'country_short' => t('Country Code'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function processWebformComposite(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processWebformComposite($element, $form_state, $complete_form);

    // Add Google Maps API key which is required by
    // https://maps.googleapis.com/maps/api/js?key=API_KEY&libraries=places
    // @see webform_location_geocomplete_js_alter()
    if (!empty($element['#api_key'])) {
      $api_key = $element['#api_key'];
    }
    else {
      /** @var \Drupal\webform\WebformThirdPartySettingsManagerInterface $third_party_settings_manager */
      $third_party_settings_manager = \Drupal::service('webform.third_party_settings_manager');
      $api_key = $third_party_settings_manager->getThirdPartySetting('webform_location_geocomplete', 'default_google_maps_api_key') ?: '';
    }
    $element['#attached']['drupalSettings']['webform']['location']['geocomplete']['api_key'] = $api_key;

    // Attach library.
    $element['#attached']['library'][] = 'webform_location_geocomplete/webform_location_geocomplete.element';

    return $element;
  }

}
