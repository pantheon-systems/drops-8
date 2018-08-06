/**
 * @file
 * JavaScript behaviors for jQuery Word and Counter Counter integration.
 */

(function ($, Drupal) {

  'use strict';

  // @see http://qwertypants.github.io/jQuery-Word-and-Character-Counter-Plugin/
  Drupal.webform = Drupal.webform || {};
  Drupal.webform.counter = Drupal.webform.counter || {};
  Drupal.webform.counter.options = Drupal.webform.counter.options || {};

  /**
   * Initialize text field and textarea word and character counter.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformCounter = {
    attach: function (context) {
      if (!$.fn.counter) {
        return;
      }

      $(context).find('.js-webform-counter').once('webform-counter').each(function () {
        var options = {
          goal: $(this).attr('data-counter-limit'),
          msg: $(this).attr('data-counter-message')
        };

        // Only word type can be defined, otherwise the counter defaults to
        // character counting.
        if ($(this).attr('data-counter-type') === 'word') {
          options.type = 'word';
        }

        options = $.extend(options, Drupal.webform.counter.options);

        // Set the target to a div that is appended to end of the input's parent container.
        options.target = $('<div></div>');
        $(this).parent().append(options.target);

        $(this).counter(options);
      });

    }
  };

})(jQuery, Drupal);
