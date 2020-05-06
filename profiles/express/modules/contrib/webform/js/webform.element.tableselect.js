/**
 * @file
 * JavaScript behaviors for tableselect enhancements.
 *
 * @see core/misc/tableselect.es6.js
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Initialize and tweak webform tableselect behavior.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformTableSelect = {
    attach: function (context) {
      $(context)
        .find('table.js-webform-tableselect')
        .once('webform-tableselect')
        .each(Drupal.webformTableSelect);
    }
  };

  /**
   * Callback used in {@link Drupal.behaviors.tableSelect}.
   */
  Drupal.webformTableSelect = function () {
    var $table = $(this);

    // Set default table rows to .selected class.
    $table.find('tr').each(function () {
      // Set table row selected for checkboxes.
      var $tr = $(this);
      if ($tr.find('input[type="checkbox"]:checked').length && !$tr.hasClass('selected')) {
        $tr.addClass('selected');
      }
    });

    // Add .selected class event handler to all tableselect elements.
    // Currently .selected is only added to tables with .select-all.
    if ($table.find('th.select-all').length === 0) {
      $table.find('td input[type="checkbox"]:enabled').on('click', function () {
        $(this).closest('tr').toggleClass('selected', this.checked);
      });
    }

    // Add click event handler to the table row that toggles the
    // checkbox or radio.
    $table.find('tr').on('click', function (event) {
      if ($.inArray(event.target.tagName, ['A', 'BUTTON', 'INPUT', 'SELECT']) !== -1) {
        return true;
      }

      var $tr = $(this);
      var $checkbox = $tr.find('td input[type="checkbox"]:enabled, td input[type="radio"]:enabled');
      if ($checkbox.length === 0) {
        return true;
      }

      $checkbox.click();
    });
  };

})(jQuery, Drupal);
