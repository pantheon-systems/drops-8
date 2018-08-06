/**
 * @file
 * Javascript behaviors for other elements.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Toggle other input (text) field.
   *
   * @param {boolean} show
   *   TRUE will display the text field. FALSE with hide and clear the text field.
   * @param {object} $element
   *   The input (text) field to be toggled.
   */
  function toggleOther(show, $element) {
    var $input = $element.find('input');
    if (show) {
      // Limit the other inputs width to the parent's container.
      $element.width($element.parent().width());
      // Display the element.
      $element.slideDown();
      // Focus and require the input.
      $input.focus().prop('required', true);
      // Restore the input's value.
      var value = $input.data('webform-value');
      if (value !== undefined) {
        $input.val(value);
        $input.get(0).setSelectionRange(0, 0);
      }
      // Refresh CodeMirror used as other element.
      $element.parent().find('.CodeMirror').each(function (index, $element) {
        $element.CodeMirror.refresh();
      });
    }
    else {
      $element.slideUp();
      // Save the input's value.
      $input.data('webform-value', $input.val());
      // Empty and un-required the input.
      $input.val('').prop('required', false);
    }
  }

  /**
   * Attach handlers to select other elements.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformSelectOther = {
    attach: function (context) {
      $(context).find('.js-webform-select-other').once('webform-select-other').each(function () {
        var $element = $(this);

        var $select = $element.find('select');
        var $otherOption = $element.find('option[value="_other_"]');
        var $input = $element.find('.js-webform-select-other-input');

        if ($otherOption.is(':selected')) {
          $input.show().find('input').prop('required', true);
        }

        $select.on('change', function () {
          toggleOther($otherOption.is(':selected'), $input);
        });
      });
    }
  };

  /**
   * Attach handlers to checkboxes other elements.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformCheckboxesOther = {
    attach: function (context) {
      $(context).find('.js-webform-checkboxes-other').once('webform-checkboxes-other').each(function () {
        var $element = $(this);
        var $checkbox = $element.find('input[value="_other_"]');
        var $input = $element.find('.js-webform-checkboxes-other-input');

        if ($checkbox.is(':checked')) {
          $input.show().find('input').prop('required', true);
        }

        $checkbox.on('change', function () {
          toggleOther(this.checked, $input);
        });
      });
    }
  };

  /**
   * Attach handlers to radios other elements.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformRadiosOther = {
    attach: function (context) {
      $(context).find('.js-webform-radios-other').once('webform-radios-other').each(function () {
        var $element = $(this);

        var $radios = $element.find('input[type="radio"]');
        var $input = $element.find('.js-webform-radios-other-input');

        if ($radios.filter(':checked').val() === '_other_') {
          $input.show().find('input').prop('required', true);
        }

        $radios.on('change', function () {
          toggleOther(($radios.filter(':checked').val() === '_other_'), $input);
        });
      });
    }
  };

  /**
   * Attach handlers to buttons other elements.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformButtonsOther = {
    attach: function (context) {
      $(context).find('.js-webform-buttons-other').once('webform-buttons-other').each(function () {
        var $element = $(this);

        var $buttons = $element.find('input[type="radio"]');
        var $input = $element.find('.js-webform-buttons-other-input');

        if ($buttons.filter(':checked').val() === '_other_') {
          $input.show().find('input').prop('required', true);
        }

        // Note: Initializing buttonset here so that we can set the onchange
        // event handler.
        // @see Drupal.behaviors.webformButtons
        var $container = $(this).find('.form-radios');
        // Remove all div and classes around radios and labels.
        $container.html($container.find('input[type="radio"], label').removeClass());
        // Create buttonset and set onchange handler.
        $container.buttonset().change(function () {
          toggleOther(($(this).find(':radio:checked').val() === '_other_'), $input);
        });
        // Disable buttonset.
        $container.buttonset('option', 'disabled', $container.find('input[type="radio"]:disabled').length);
        // Turn buttonset off/on when the input is disabled/enabled.
        // @see webform.states.js
        $container.on('webform:disabled', function () {
          $container.buttonset('option', 'disabled', $container.find('input[type="radio"]:disabled').length);
        });
      });
    }
  };

})(jQuery, Drupal);
