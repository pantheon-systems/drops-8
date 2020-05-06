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
  Drupal.webform.chosen.options.width = Drupal.webform.chosen.options.width || '100%';
  Drupal.webform.chosen.options.widthInline = Drupal.webform.chosen.options.widtInline || '50%';

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

      // Add HTML5 required attribute support.
      // Checking for $oldChosen to prevent duplicate workarounds from
      // being applied.
      // @see https://github.com/harvesthq/chosen/issues/515
      if (!$.fn.oldChosen) {
        $.fn.oldChosen = $.fn.chosen;
        $.fn.chosen = function (options) {
          var select = $(this);
          var is_creating_chosen = !!options;
          if (is_creating_chosen && select.css('position') === 'absolute') {
            select.removeAttr('style');
          }
          var ret = select.oldChosen(options);
          if (is_creating_chosen && select.css('display') === 'none') {
            select.attr('style', 'display:visible; position:absolute; width:0px; height: 0px; clip:rect(0,0,0,0)');
            select.attr('tabindex', -1);
          }
          return ret;
        };
      }

      $(context)
        .find('select.js-webform-chosen, .js-webform-chosen select')
        .once('webform-chosen')
        .each(function () {
          var $select = $(this);
          // Check for .chosen-enable to prevent the chosen.module and
          // webform.module from both initializing the chosen select element.
          if ($select.hasClass('chosen-enable')) {
            return;
          }

          var options = {};
          if ($select.parents('.webform-element--title-inline').length) {
            options.width = Drupal.webform.chosen.options.widthInline;
          }
          options = $.extend(options, Drupal.webform.chosen.options);
          if ($select.data('placeholder')) {
            if ($select.prop('multiple')) {
              options.placeholder_text_multiple = $select.data('placeholder');
            }
            else {
              // Clear option value so that placeholder is displayed.
              $select.find('option[value=""]').html('');
              // Allow single option to be deselected.
              options.allow_single_deselect = true;
            }
          }
          if ($select.data('limit')) {
            options.max_selected_options = $select.data('limit');
          }

          // Remove required attribute from IE11 which breaks
          // HTML5 clientside validation.
          if (window.navigator.userAgent.indexOf('Trident/') !== false
            && $select.attr('multiple')
            && $select.attr('required')) {
            $select.removeAttr('required');
          }

          $select.chosen(options);
        });
    }
  };

  var $document = $(document);

  // Refresh chosen (select) widgets when they are disabled/enabled.
  $document.on('state:disabled', function (e) {
    var $chosen = $(e.target).find('.js-webform-chosen');
    if ($(e.target).hasClass('js-webform-chosen')) {
      $chosen.add(e.target);
    }
    $chosen.trigger('chosen:updated');
  });

})(jQuery, Drupal);
