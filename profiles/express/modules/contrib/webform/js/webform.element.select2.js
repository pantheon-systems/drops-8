/**
 * @file
 * JavaScript behaviors for Select2 integration.
 */

(function ($, Drupal) {

  'use strict';

  // @see https://select2.github.io/options.html
  Drupal.webform = Drupal.webform || {};
  Drupal.webform.select2 = Drupal.webform.select2 || {};
  Drupal.webform.select2.options = Drupal.webform.select2.options || {};

  /**
   * Initialize Select2 support.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformSelect2 = {
    attach: function (context) {
      if (!$.fn.select2) {
        return;
      }

      $(context)
        .find('select.js-webform-select2, .js-webform-select2 select')
        .once('webform-select2')
        // http://stackoverflow.com/questions/14313001/select2-not-calculating-resolved-width-correctly-if-select-is-hidden
        .css('width', '100%')
        .select2(Drupal.webform.select2.options);


      /**
       * ISSUE:
       * Hiding/showing element via #states API cause select2 dropdown to appear in the wrong position.
       *
       * WORKAROUND:
       * Close (aka hide) select2 dropdown when #states API hides or shows an element.
       *
       * Steps to reproduce:
       * - Add custom 'Submit button(s)'
       * - Hide submit button
       * - Save
       * - Open 'Submit button(s)' dialog
       *
       * Dropdown body is positioned incorrectly when dropdownParent isn't statically positioned.
       * @see https://github.com/select2/select2/issues/3303
       */
      $(function () {
        $(document).on('state:visible', function (e) {
          $('select.js-webform-select2, .js-webform-select2 select').select2('close');
        });
      });

    }
  };

})(jQuery, Drupal);
