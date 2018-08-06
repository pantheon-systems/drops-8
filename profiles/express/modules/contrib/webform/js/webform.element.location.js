/**
 * @file
 * JavaScript behaviors for Geocomplete location integration.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  // @see https://ubilabs.github.io/geocomplete/
  // @see https://developers.google.com/maps/documentation/javascript/reference?csw=1#MapOptions
  Drupal.webform = Drupal.webform || {};
  Drupal.webform.locationGeocomplete = Drupal.webform.locationGeocomplete || {};
  Drupal.webform.locationGeocomplete.options = Drupal.webform.locationGeocomplete.options || {};

  /**
   * Initialize location Geocompletion.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformLocationGeocomplete = {
    attach: function (context) {
      if (!$.fn.geocomplete) {
        return;
      }

      $(context).find('div.js-form-type-webform-location').once('webform-location').each(function () {
        var $element = $(this);
        var $input = $element.find('.webform-location-geocomplete');
        var $map = null;
        if ($input.attr('data-webform-location-map')) {
          $map = $('<div class="webform-location-map"><div class="webform-location-map--container"></div></div>').insertAfter($input).find('.webform-location-map--container');
        }

        var options = $.extend({
          details: $element,
          detailsAttribute: 'data-webform-location-attribute',
          types: ['geocode'],
          map: $map,
          mapOptions: {
            disableDefaultUI: true,
            zoomControl: true
          }
        }, Drupal.webform.locationGeocomplete.options);

        var $geocomplete = $input.geocomplete(options);

        $geocomplete.on('input', function () {
          // Reset attributes on input.
          $element.find('[data-webform-location-attribute]').val('');
        }).on('blur', function () {
          // Make sure to get attributes on blur.
          if ($element.find('[data-webform-location-attribute="location"]').val() === '') {
            var value = $geocomplete.val();
            if (value) {
              $geocomplete.geocomplete('find', value);
            }
          }
        });

        // If there is default value look up location's attributes, else see if
        // the default value should be set to the browser's current geolocation.
        var value = $geocomplete.val();
        if (value) {
          $geocomplete.geocomplete('find', value);
        }
        else if (navigator.geolocation && $geocomplete.attr('data-webform-location-geolocation')) {
          navigator.geolocation.getCurrentPosition(function (position) {
            $geocomplete.geocomplete('find', position.coords.latitude + ', ' + position.coords.longitude);
          });
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
