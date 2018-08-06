/**
 * @file
 * JavaScript behaviors for range element integration.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Enhance HTML5 range element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformRange = {
    attach: function (context) {
      $(context).find('.form-range[data-range-output]').once('webform-range').each(function () {
        var $element = $(this);
        // Handle browser that don't support the HTML5 range input.
        if (Modernizr.inputtypes.range === false) {
          return;
        }

        var prefix = $element.attr('data-range-output-prefix');
        var suffix = $element.attr('data-range-output-suffix');

        // Display range input's output to the end user.
        var html = '';
        html += '<div class="form-range-output-container">';
        html += (prefix ? '<span class="field-prefix">' + prefix + '</span>' : '');
        html += '<input type="number" min="' + $element.attr('min') + '" max="' + $element.attr('max') + '" step="' + $element.attr('step') + '" class="form-range-output form-number" />';
        html += (suffix ? '<span class="field-suffix">' + suffix + '</span>' : '');
        html += '</div>';

        var height = parseInt($element.outerHeight()) || 24;
        var $outputContainer = $(html);

        // Set the container element's line height which will vertically
        // align the range widget and the output.
        $outputContainer.find('input, span').css({
          height: height + 'px',
          lineHeight: height + 'px'
        });

        var $output = $outputContainer.find('input');
        $output[0].value = $element[0].value;
        $element
          .after($outputContainer)
          .css({float: 'left'});

        // Sync $element and $output.
        $element.on('input', function () {
          $output[0].value = $element[0].value;
        });
        $output.on('input', function () {
          $element[0].value = $output[0].value;
        });
      });
    }
  };

})(jQuery, Drupal);
