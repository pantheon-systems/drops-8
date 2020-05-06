/**
 * @file
 * JavaScript behaviors for Algolia places location integration.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  // @see https://github.com/algolia/places
  // @see https://community.algolia.com/places/documentation.html#options
  Drupal.webform = Drupal.webform || {};
  Drupal.webform.locationPlaces = Drupal.webform.locationPlaces || {};
  Drupal.webform.locationPlaces.options = Drupal.webform.locationPlaces.options || {};

  var mapping = {
    lat: 'lat',
    lng: 'lng',
    name: 'name',
    postcode: 'postcode',
    locality: 'locality',
    city: 'city',
    administrative: 'administrative',
    country: 'country',
    countryCode: 'country_code',
    county: 'county',
    suburb: 'suburb'
  };

  /**
   * Initialize location places.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformLocationPlaces = {
    attach: function (context) {
      if (!window.places) {
        return;
      }

      $(context).find('.js-webform-type-webform-location-places').once('webform-location-places').each(function () {
        var $element = $(this);
        var $input = $element.find('.webform-location-places');

        // Prevent the 'Enter' key from submitting the form.
        $input.keydown(function (event) {
          if (event.keyCode === 13) {
            event.preventDefault();
          }
        });

        var options = $.extend({
          type: 'address',
          useDeviceLocation: true,
          container: $input.get(0)
        }, Drupal.webform.locationPlaces.options);

        // Add application id and API key.
        if (drupalSettings.webform.location.places.app_id && drupalSettings.webform.location.places.api_key) {
          options.appId = drupalSettings.webform.location.places.app_id;
          options.apiKey = drupalSettings.webform.location.places.api_key;
        }

        var placesAutocomplete = window.places(options);

        // Disable autocomplete.
        // @see https://gist.github.com/niksumeiko/360164708c3b326bd1c8
        var isChrome = (/chrom(e|ium)/.test(window.navigator.userAgent.toLowerCase()));
        $input.attr('autocomplete', (isChrome) ? 'chrome-off-' + Math.floor(Math.random() * 100000000) : 'off');

        // Sync values on change and clear events.
        placesAutocomplete.on('change', function (e) {
          $.each(mapping, function (source, destination) {
            var value = (source === 'lat' || source === 'lng' ? e.suggestion.latlng[source] : e.suggestion[source]) || '';
            setValue(destination, value);
          });
        });
        placesAutocomplete.on('clear', function (e) {
          $.each(mapping, function (source, destination) {
            setValue(destination, '');
          });
        });

        // If there is no default value see if the default value should be set
        // to the browser's current geolocation.
        // @see https://community.algolia.com/places/examples.html#dynamic-form
        if ($input.val() === ''
          && window.navigator.geolocation
          && $input.attr('data-webform-location-places-geolocation')) {

          placesAutocomplete.on('reverse', function (e) {
            var suggestion = e.suggestions[0];
            $input.val(suggestion.value);
            $.each(mapping, function (source, destination) {
              var value = (source === 'lat' || source === 'lng' ? suggestion.latlng[source] : suggestion[source]) || '';
              setValue(destination, value);
            });
          });

          window.navigator.geolocation.getCurrentPosition(function (response) {
            var coords = response.coords;
            var lat = coords.latitude.toFixed(6);
            var lng = coords.longitude.toFixed(6);
            placesAutocomplete.reverse(lat + ',' + lng);
          });
        }

        /**
         * Set attribute value.
         *
         * @param {string} name
         *   The attribute name
         * @param {string} value
         *   The attribute value
         */
        function setValue(name, value) {
          var inputSelector = ':input[data-webform-location-places-attribute="' + name + '"]';
          $element.find(inputSelector).val(value);
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings);

