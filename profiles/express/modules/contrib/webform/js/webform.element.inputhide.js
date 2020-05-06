/**
 * @file
 * JavaScript behaviors for input hiding.
 */

(function ($, Drupal) {

  'use strict';

  var isChrome = (/chrom(e|ium)/.test(window.navigator.userAgent.toLowerCase()));

  /**
   * Initialize input hiding.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformInputHide = {
    attach: function (context) {
      // Apply chrome fix to prevent password input from being autofilled.
      // @see https://stackoverflow.com/questions/15738259/disabling-chrome-autofill
      if (isChrome) {
        $(context).find('form:has(input.js-webform-input-hide)')
          .once('webform-input-hide-chrome-workaround')
          .each(function () {
            $(this).prepend('<input style="display:none" type="text" name="chrome_autocomplete_username"/><input style="display:none" type="password" name="chrome_autocomplete_password"/>');
          });
      }

      // Convert text based inputs to password input on blur.
      $(context).find('input.js-webform-input-hide')
        .once('webform-input-hide')
        .each(function () {
          var type = this.type;
          // Initialize input hiding.
          this.type = 'password';

          // Attach blur and focus event handlers.
          $(this)
            .on('blur', function () {
              this.type = 'password';
              $(this).attr('autocomplete', (isChrome) ? 'chrome-off-' + Math.floor(Math.random() * 100000000) : 'off');
            })
            .on('focus', function () {
              this.type = type;
              $(this).removeAttr('autocomplete');
            });
        });
    }
  };

})(jQuery, Drupal);
