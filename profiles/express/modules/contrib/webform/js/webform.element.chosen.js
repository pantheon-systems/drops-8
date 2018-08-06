/**
 * @file
 * JavaScript behaviors for Chosen integration.
 */

(function ($, Drupal) {

  'use strict';

  // @see https://harvesthq.github.io/chosen/options.html
  Drupal.webform = Drupal.webform || {};
  Drupal.webform.chosen = Drupal.webform.chosen || {};
  Drupal.webform.chosen.options = Drupal.webform.chosen.options || {};

  /**
   * Initialize Chosen support.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformChosen = {
    attach: function (context) {
      if (!$.fn.chosen) {
        return;
      }

      var options = $.extend({width: '100%'}, Drupal.webform.chosen.options);

      $(context)
        .find('select.js-webform-chosen, .js-webform-chosen select')
        .once('webform-chosen')
        .chosen(options);
    }
  };

})(jQuery, Drupal);
