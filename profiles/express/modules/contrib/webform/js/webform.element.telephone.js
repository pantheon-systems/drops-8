/**
 * @file
 * JavaScript behaviors for Telephone element.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  // @see https://github.com/jackocnr/intl-tel-input#options
  Drupal.webform = Drupal.webform || {};
  Drupal.webform.intlTelInput = Drupal.webform.intlTelInput || {};
  Drupal.webform.intlTelInput.options = Drupal.webform.intlTelInput.options || {};

  /**
   * Initialize Telephone international element.
   * @see http://intl-tel-input.com/node_modules/intl-tel-input/examples/gen/is-valid-number.html
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformTelephoneInternational = {
    attach: function (context) {
      if (!$.fn.intlTelInput) {
        return;
      }

      $(context).find('input.js-webform-telephone-international').once('webform-telephone-international').each(function () {
        var $telephone = $(this);

        // Add error message container.
        var $error = $('<div class="form-item--error-message">' + Drupal.t('Invalid phone number') + '</div>').hide();
        $telephone.closest('.js-form-item').append($error);

        // @todo: Figure out how to lazy load utilsScript (build/js/utils.js).
        // @see https://github.com/jackocnr/intl-tel-input#utilities-script
        var options = $.extend({
          nationalMode: false,
          initialCountry: $telephone.attr('data-webform-telephone-international-initial-country') || ''
        }, Drupal.webform.intlTelInput.options);
        $telephone.intlTelInput(options);

        var reset = function () {
          $telephone.removeClass('error');
          $error.hide();
        };

        $telephone.blur(function () {
          reset();
          if ($.trim($telephone.val())) {
            if (!$telephone.intlTelInput('isValidNumber')) {
              $telephone.addClass('error');
              $error.show();
            }
          }
        });

        $telephone.on('keyup change', reset);
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
