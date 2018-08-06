/**
 * @file
 * JavaScript behaviors for jQuery UI buttons (checkboxradio) element integration.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Create jQuery UI buttons (checkboxradio) element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformButtonsCheckboxRadio = {
    attach: function (context) {
      $(context).find('.js-webform-buttons .form-radios, .js-webform-buttons.form-radios, .js-webform-buttons .js-webform-radios').once('webform-buttons').each(function () {
        // Remove all div and classes around radios and labels.
        $(this).html($(this).find('input[type="radio"], label').removeClass());

        // Get radios.
        var $input = $(this).find('input[type="radio"]');

        // Create checkboxradio.
        $input.checkboxradio({'icon': false});

        // Disable checkboxradio.
        $input.checkboxradio('option', 'disabled', $input.is(':disabled'));

        // Turn checkboxradio off/on when the input is disabled/enabled.
        // @see webform.states.js
        $input.on('webform:disabled', function () {
          $input.checkboxradio('option', 'disabled', $input.is(':disabled'));
        });
      });
    }
  };

})(jQuery, Drupal);
