/**
 * @file
 * Override polyfill for HTML5 date input and provide support for custom date formats.
 */

(function ($, Modernizr, Drupal) {

  'use strict';

  // @see http://api.jqueryui.com/datepicker/
  Drupal.webform = Drupal.webform || {};
  Drupal.webform.datePicker = Drupal.webform.datePicker || {};
  Drupal.webform.datePicker.options = Drupal.webform.datePicker.options || {};

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
      $context.find('input[data-drupal-date-format]').once('datePicker').each(function () {
        var $input = $(this);

        // Skip if date inputs are supported by the browser and input is not a text field.
        // @see \Drupal\webform\Element\WebformDatetime
        if (window.Modernizr && Modernizr.inputtypes && Modernizr.inputtypes.date === true && $input.attr('type') !== 'text') {
          return;
        }

        var options = $.extend({
          changeMonth: true,
          changeYear: true
        }, Drupal.webform.datePicker.options);

        // Add datepicker button.
        if ($input.hasData && $input.hasData('datepicker-button')) {
          options = $.extend({
            showOn: 'both',
            buttonImage: settings.webform.datePicker.buttonImage,
            buttonImageOnly: true,
            buttonText: Drupal.t('Select date')
          }, Drupal.webform.datePicker.options);
        }

        var dateFormat = $input.data('drupalDateFormat');

        // The date format is saved in PHP style, we need to convert to jQuery
        // datepicker.
        // @see http://stackoverflow.com/questions/16702398/convert-a-php-date-format-to-a-jqueryui-datepicker-date-format
        // @see http://php.net/manual/en/function.date.php
        options.dateFormat = dateFormat
        // Year.
          .replace('Y', 'yy') // A full numeric representation of a year, 4 digits (1999 or 2003)
          .replace('y', 'y') // A two digit representation of a year (99 or 03)
          // Month.
          .replace('F', 'MM') // A full textual representation of a month, such as January or March	(January through December)
          .replace('m', 'mm') // Numeric representation of a month, with leading zeros (01 through 12)
          .replace('M', 'M') // A short textual representation of a month, three letters (Jan through Dec)
          .replace('n', 'm') // Numeric representation of a month, without leading zeros (1 through 12)
          // Day.
          .replace('d', 'dd') // Day of the month, 2 digits with leading zeros (01 to 31)
          .replace('D', 'D') // A textual representation of a day, three letters (Mon through Sun)
          .replace('j', 'd') // Day of the month without leading zeros (1 to 31)
          .replace('l', 'DD'); // A full textual representation of the day of the week (Sunday through Saturday)

        // Add min and max date if set on the input.
        if ($input.attr('min')) {
          options.minDate = $input.attr('min');
        }
        if ($input.attr('max')) {
          options.maxDate = $input.attr('max');
        }

        // Add min/max year to data range.
        if (!options.yearRange && $input.data('min-year') && $input.data('max-year')) {
          options.yearRange = $input.data('min-year') + ':' + $input.attr('data-max-year');
        }

        // First day of the week.
        options.firstDay = settings.webform.dateFirstDay;

        // Days of the week.
        // @see https://stackoverflow.com/questions/2968414/disable-specific-days-of-the-week-on-jquery-ui-datepicker
        if ($input.attr('data-days')) {
          var days = $input.attr('data-days').split(',');
          options.beforeShowDay = function (date) {
            var day = date.getDay().toString();
            return [(days.indexOf(day) !== -1) ? true : false];
          };
        }

        // Disable autocomplete.
        // @see https://gist.github.com/niksumeiko/360164708c3b326bd1c8
        var isChrome = (/chrom(e|ium)/.test(window.navigator.userAgent.toLowerCase()));
        $input.attr('autocomplete', (isChrome) ? 'chrome-off-' + Math.floor(Math.random() * 100000000) : 'off');

        $input.datepicker(options);
      });
    }
    // Issue #2983363: Datepicker is being detached when multiple files are
    // uploaded.
    /*
    },
    detach: function (context, settings, trigger) {
      if (trigger === 'unload') {
        $(context).find('input[data-drupal-date-format]').findOnce('datePicker').datepicker('destroy');
      }
    }
    */
  };

})(jQuery, Modernizr, Drupal);
