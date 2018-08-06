<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a webform element for a location element.
 *
 * @FormElement("webform_location")
 */
class WebformLocation extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + [
      '#theme' => 'webform_composite_location',
      '#api_key' => '',
      '#hidden' => FALSE,
      '#geolocation' => FALSE,
      '#map' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements() {
    // @see https://developers.google.com/maps/documentation/javascript/geocoding#GeocodingAddressTypes
    $attributes = [];
    $attributes['lat'] = [
      '#title' => t('Latitude'),
    ];
    $attributes['lng'] = [
      '#title' => t('Longitude'),
    ];
    $attributes['location'] = [
      '#title' => t('Location'),
    ];
    $attributes['formatted_address'] = [
      '#title' => t('Formatted Address'),
    ];
    $attributes['street_address'] = [
      '#title' => t('Street Address'),
    ];
    $attributes['street_number'] = [
      '#title' => t('Street Number'),
    ];
    $attributes['postal_code'] = [
      '#title' => t('Postal Code'),
    ];
    $attributes['locality'] = [
      '#title' => t('Locality'),
    ];
    $attributes['sublocality'] = [
      '#title' => t('City'),
    ];
    $attributes['administrative_area_level_1'] = [
      '#title' => t('State/Province'),
    ];
    $attributes['country'] = [
      '#title' => t('Country'),
    ];
    $attributes['country_short'] = [
      '#title' => t('Country Code'),
    ];

    foreach ($attributes as $name => &$attribute_element) {
      $attribute_element['#type'] = 'textfield';

      $attribute_element['#attributes'] = [
        'data-webform-location-attribute' => $name,
      ];
    }

    $elements = [];
    $elements['value'] = [
      '#type' => 'textfield',
      '#title' => t('Address'),
      '#attributes' => [
        'class' => ['webform-location-geocomplete'],
      ],
    ];

    $elements += $attributes;
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderCompositeFormElement($element) {
    $element = WebformCompositeBase::preRenderCompositeFormElement($element);

    // Hide location element webform display only if #geolocation is also set.
    if (!empty($element['#hidden']) && !empty($element['#geolocation'])) {
      $element['#wrapper_attributes']['style'] = 'display: none';
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function processWebformComposite(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processWebformComposite($element, $form_state, $complete_form);

    // Composite elements should always be displayed and rendered so that
    // location data can be populated, so #access is really just converting the
    // readonly elements to hidden elements.
    $composite_elements = static::getCompositeElements();
    foreach ($composite_elements as $composite_key => $composite_element) {
      if ($composite_key != 'value') {
        if (isset($element[$composite_key]['#access']) && $element[$composite_key]['#access'] === FALSE) {
          unset($element[$composite_key]['#access']);
          $element[$composite_key]['#type'] = 'hidden';
        }
        elseif (!empty($element['#hidden']) && !empty($element['#geolocation'])) {
          $element[$composite_key]['#type'] = 'hidden';
        }
        else {
          $element[$composite_key]['#attributes']['class'][] = 'webform-readonly';
          $element[$composite_key]['#readonly'] = 'readonly';
        }
      }
    }

    // Set required.
    if (isset($element['#required'])) {
      $element['value']['#required'] = $element['#required'];
    }

    // Set Geolocation detection attribute.
    if (!empty($element['#geolocation'])) {
      $element['value']['#attributes']['data-webform-location-geolocation'] = 'data-webform-location-geolocation';
    }

    // Set Map attribute.
    if (!empty($element['#map']) && empty($element['#hidden'])) {
      $element['value']['#attributes']['data-webform-location-map'] = 'data-webform-location-map';
    }

    // Add Google Maps API key which is required by
    // https://maps.googleapis.com/maps/api/js?key=API_KEY&libraries=places
    // @see webform_js_alter()
    $api_key = (!empty($element['#api_key'])) ? $element['#api_key'] : \Drupal::config('webform.settings')->get('element.default_google_maps_api_key');
    $element['#attached']['drupalSettings']['webform']['location']['google_maps_api_key'] = $api_key;

    $element['#attached']['library'][] = 'webform/webform.element.location';

    $element['#element_validate'] = [[get_called_class(), 'validateWebformLocation']];

    return $element;
  }

  /**
   * Validates location.
   */
  public static function validateWebformLocation(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = $element['#value'];

    $has_access = (!isset($element['#access']) || $element['#access'] === TRUE);
    if ($has_access && !empty($element['#required']) && empty($value['location'])) {
      $t_args = ['@title' => !empty($element['#title']) ? $element['#title'] : t('Location')];
      $form_state->setError($element, t('The @title is not valid.', $t_args));
    }
  }

}
