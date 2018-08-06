/**
 * @file
 * JavaScript behaviors for jquery.inputmask integration.
 */

(function ($, Drupal) {

  'use strict';

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
