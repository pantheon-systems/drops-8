/**
 * @file
 * JavaScript behaviors for jquery.inputmask integration.
 */

(function ($, Drupal) {

  'use strict';

  // Revert: Set currency prefix to empty by default #2066.
  // @see https://github.com/RobinHerbots/Inputmask/issues/2066
  if (window.Inputmask) {
    window.Inputmask.extendAliases({
      currency: {
        prefix: '$ ',
        groupSeparator: ',',
        alias: 'numeric',
        placeholder: '0',
        autoGroup: true,
        digits: 2,
        digitsOptional: false,
        clearMaskOnLostFocus: false
      }
    });
  }

  /**
   * Initialize input masks.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformInputMask = {
    attach: function (context) {
      if (!$.fn.inputmask) {
        return;
      }

      $(context).find('input.js-webform-input-mask').once('webform-input-mask').inputmask();
    }
  };

})(jQuery, Drupal);
