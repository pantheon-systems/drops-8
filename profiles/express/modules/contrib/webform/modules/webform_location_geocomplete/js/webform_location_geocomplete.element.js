/**
 * @file
 * JavaScript behaviors for Geocomplete location integration.
 */

(function ($, Drupal) {

  'use strict';

  // @see https://ubilabs.github.io/geocomplete/
  // @see https://developers.google.com/maps/documentation/javascript/reference?csw=1#MapOptions
  Drupal.webform = Drupal.webform || {};
  Drupal.webform.locationGeocomplete = Drupal.webform.locationGeocomplete || {};
  Drupal.webform.locationGeocomplete.options = Drupal.webform.locationGeocomplete.options || {};

  /**
   * Initialize location geocomplete.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformLocationGeocomplete = {
    attach: function (context) {
      if (!$.fn.geocomplete) {
        return;
      }

      $(context).find('.js-webform-type-webform-location-geocomplete').once('webform-location-geocomplete').each(function () {
        var $element = $(this);
        var $input = $element.find('.webform-location-geocomplete');

        // Display a map.
        var $map = null;
        if ($input.attr('data-webform-location-geocomplete-map')) {
          $map = $('<div class="webform-location-geocomplete-map"><div class="webform-location-geocomplete-map--container"></div></div>').insertAfter($input).find('.webform-location-geocomplete-map--container');
        }

        var options = $.extend({
          details: $element,
          detailsAttribute: 'data-webform-location-geocomplete-attribute',
          types: ['geocode'],
          map: $map,
          geocodeAfterResult: false,
          restoreValueAfterBlur: true,
          mapOptions: {
            disableDefaultUI: true,
            zoomControl: true
          }
        }, Drupal.webform.locationGeocomplete.options);

        var $geocomplete = $input.geocomplete(options);

        // If there is default value look up location's attributes, else see if
        // the default value should be set to the browser's current geolocation.
        var value = $geocomplete.val();
        if (value) {
          $geocomplete.geocomplete('find', value);
        }
        else if (window.navigator.geolocation && $geocomplete.attr('data-webform-location-geocomplete-geolocation')) {
          window.navigator.geolocation.getCurrentPosition(function (position) {
            $geocomplete.geocomplete('find', position.coords.latitude + ', ' + position.coords.longitude);
          });
        }
      });
    }
  };

})(jQuery, Drupal);
