/**
 * @file
 * Override polyfill for HTML5 date input and provide support for custom date formats.
 */

(function ($, Modernizr, Drupal) {

  'use strict';

  /**
   * Attach datepicker fallback on date elements.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior. Accepts in `settings.date` an object listing
   *   elements to process, keyed by the HTML ID of the form element containing
   *   the human-readable value. Each element is an datepicker settings object.
   * @prop {Drupal~behaviorDetach} detach
   *   Detach the behavior destroying datepickers on effected elements.
   */
  Drupal.behaviors.date = {
    attach: function (context, settings) {
      var $context = $(context);
      // Skip if date are supported by the browser.
      if (Modernizr.inputtypes.date === true) {
        return;
      }
      $context.find('input[data-drupal-date-format]').once('datePicker').each(function () {
        var $input = $(this);
        var datepickerSettings = {};
        var dateFormat = $input.data('drupalDateFormat');
        // The date format is saved in PHP style, we need to convert to jQuery
        // datepicker.
        // @see http://stackoverflow.com/questions/16702398/convert-a-php-date-format-to-a-jqueryui-datepicker-date-format
        datepickerSettings.dateFormat = dateFormat
          // Year.
          .replace('Y', 'yy')
          // Month.
          .replace('F', 'MM')
          .replace('m', 'mm')
          .replace('n', 'm')
          // Date.
          .replace('d', 'dd');

        // Add min and max date if set on the input.
        if ($input.attr('min')) {
          datepickerSettings.minDate = $.datepicker.formatDate(datepickerSettings.dateFormat, $.datepicker.parseDate('yy-mm-dd', $input.attr('min')));
        }
        if ($input.attr('max')) {
          datepickerSettings.maxDate = $.datepicker.formatDate(datepickerSettings.dateFormat, $.datepicker.parseDate('yy-mm-dd', $input.attr('max')));
        }

        // Format default value.
        if ($input.val()) {
          $input.val($.datepicker.formatDate(datepickerSettings.dateFormat, $.datepicker.parseDate('yy-mm-dd', $input.val())));
        }

        $input.datepicker(datepickerSettings);
      });
    },
    detach: function (context, settings, trigger) {
      if (trigger === 'unload') {
        $(context).find('input[data-drupal-date-format]').findOnce('datePicker').datepicker('destroy');
      }
    }
  };

})(jQuery, Modernizr, Drupal);
