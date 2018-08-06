/**
 * @file
 * JavaScript behaviors for jQuery UI buttons (buttonset) element integration.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Create jQuery UI buttons (buttonset) element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformButtonsButtonSet = {
    attach: function (context) {
      $(context).find('.js-webform-buttons .form-radios, .js-webform-buttons.form-radios, .js-webform-buttons .js-webform-radios').once('webform-buttons').each(function () {
        var $input = $(this);

        // Remove all div and classes around radios and labels.
        $input.html($input.find('input[type="radio"], label').removeClass());

        // Create buttonset.
        $input.buttonset();

        // Disable buttonset.
        $input.buttonset('option', 'disabled', $input.find('input[type="radio"]:disabled').length);

        // Turn buttonset off/on when the input is disabled/enabled.
        // @see webform.states.js
        $input.on('webform:disabled', function () {
          $input.buttonset('option', 'disabled', $input.find('input[type="radio"]:disabled').length);
        });
      });
    }
  };

})(jQuery, Drupal);
