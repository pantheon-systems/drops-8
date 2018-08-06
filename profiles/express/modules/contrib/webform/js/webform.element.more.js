/**
 * @file
 * JavaScript behaviors for element (read) more.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Element (read) more.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformElementMore = {
    attach: function (context) {
      $(context).find('.js-webform-element-more').once('webform-element-more').each(function (event) {
        var $more = $(this);
        $more.find('a').on('click', function() {
          $more.toggleClass('is-open');
          $more.find('.webform-element-more--content').slideToggle();
          event.preventDefault();
        })
      });
    }
  };

})(jQuery, Drupal);
