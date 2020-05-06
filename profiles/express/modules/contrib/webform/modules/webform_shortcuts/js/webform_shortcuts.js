/**
 * @file
 * JavaScript behaviors for webform builder shortcuts.
 *
 * @see webform_shortcuts_preprocess_block()
 */

(function ($, drupalSettings) {

  'use strict';

  var shortcuts = drupalSettings.webform.shortcuts;

  // Add element.
  if (shortcuts.addElement) {
    $(document).bind('keydown', shortcuts.addElement, function () {
      $('#webform-ui-add-element').click();
    });
  }

  // Add page.
  if (shortcuts.addPage) {
    $(document).bind('keydown', shortcuts.addPage, function () {
      $('#webform-ui-add-page').focus().click();
    });
  }

  // Add layout.
  if (shortcuts.addLayout) {
    $(document).bind('keydown', shortcuts.addLayout, function () {
      $('#webform-ui-add-layout').click();
    });
  }

  // Save element or elements.
  if (shortcuts.saveElements) {
    $(document).bind('keydown', shortcuts.saveElements, function () {
      var $dialogSubmit = $('form.webform-ui-element-form [data-drupal-selector="edit-submit"]');
      if ($dialogSubmit.length) {
        $dialogSubmit.click();
      }
      else {
        $('form.webform-edit-form [data-drupal-selector="edit-submit"]').click();
      }
    });
  }

  // Reset elements.
  if (shortcuts.resetElements) {
    $(document).bind('keydown', shortcuts.resetElements, function () {
      $('form.webform-edit-form [data-drupal-selector="edit-reset"]').click();
    });
  }

  // Toggle weight.
  if (shortcuts.toggleWeights) {
    $(document).bind('keydown', shortcuts.toggleWeights, function () {
      $('.tabledrag-toggle-weight').eq(0).click();
    });
  }

})(jQuery, drupalSettings);
