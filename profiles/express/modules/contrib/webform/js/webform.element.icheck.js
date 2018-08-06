/**
 * @file
 * JavaScript behaviors for iCheck integration.
 */

(function ($, Drupal) {

  'use strict';

  // @see http://icheck.fronteed.com/#options
  Drupal.webform = Drupal.webform || {};
  Drupal.webform.iCheck = Drupal.webform.iCheck || {};
  Drupal.webform.iCheck.options = Drupal.webform.iCheck.options || {};

  function initializeCheckbox($input, options) {
    $input.addClass('js-webform-icheck')
      .iCheck(options)
      // @see https://github.com/fronteed/iCheck/issues/244
      .on('ifChecked', function (e) {
        $(e.target).attr('checked', 'checked').change();
      })
      .on('ifUnchecked', function (e) {
        $(e.target).removeAttr('checked').change();
      });
  }
  /**
   * Enhance checkboxes and radios using iCheck.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformICheck = {
    attach: function (context) {
      if (!$.fn.iCheck) {
        return;
      }
      $('input[data-webform-icheck]', context).each(function () {
        var $input = $(this);
        var icheck = $input.attr('data-webform-icheck');

        var options = $.extend({
          checkboxClass: 'icheckbox_' + icheck,
          radioClass: 'iradio_' + icheck
        }, Drupal.webform.iCheck.options);

        // The line skin requires that the label be added to the options.
        // @see http://icheck.fronteed.com/#skin-line
        if (icheck.indexOf('line') === 0) {
          var $label = $input.parent().find('label[for="' + $input.attr('id') + '"]');

          // Set insert with label text.
          options.insert = '<div class="icheck_line-icon"></div>' + $label.text();

          // Make sure checkbox is outside the label and then remove the label.
          $label.insertAfter($input).remove();
        }

        $input.addClass('js-webform-icheck')
          .iCheck(options)
          // @see https://github.com/fronteed/iCheck/issues/244
          .on('ifChecked', function (e) {
            $(e.target).attr('checked', 'checked').change();
          })
          .on('ifUnchecked', function (e) {
            $(e.target).removeAttr('checked').change();
          });
      });
    }
  };

  /**
   * Enhance table select checkall.
   *
   * ISSUE: Select all is not sync'd with checkboxes because iCheck overrides all existing event handlers.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformICheckTableSelectAll = {
    attach: function (context) {
      if (!$.fn.iCheck) {
        return;
      }

      $('table[data-webform-icheck] th.select-all').bind('DOMNodeInserted', function () {
        $(this).unbind('DOMNodeInserted');
        $(this).find('input[type="checkbox"]').each(function () {
          var icheck = $(this).closest('table[data-webform-icheck]').attr('data-webform-icheck');

          var options = $.extend({
            checkboxClass: 'icheckbox_' + icheck,
            radioClass: 'iradio_' + icheck
          }, Drupal.webform.iCheck.options);

          $(this).iCheck(options);
        })
        .on('ifChanged', function () {
          var _index = $(this).parents('th').index() + 1;
          $(this).parents('thead').next('tbody').find('tr td:nth-child(' + _index + ') input')
            .iCheck(!$(this).is(':checked') ? 'check' : 'uncheck')
            .iCheck($(this).is(':checked') ? 'check' : 'uncheck');
        });
      });
    }
  };

  /**
   * Sync iCheck element when checkbox/radio is enabled/disabled via the #states.
   *
   * @see core/misc/states.js
   */
  if ($.fn.iCheck) {
    $(document).on('state:disabled', function (e) {
      if ($(e.target).hasClass('.js-webform-icheck')) {
        $(e.target).iCheck(e.value ? 'disable' : 'enable');
      }

      $(e.target).iCheck(e.value ? 'disable' : 'enable');
    });
  }


})(jQuery, Drupal);
