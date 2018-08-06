/**
 * @file
 * Title attribute functions.
 */

(function ($, Drupal, document) {

  'use strict';

  var fieldName = '[name="attributes[title]"]';

  /**
   * Automatically populate the title attribute.
   */
  $(document).bind('linkit.autocomplete.select', function (triggerEvent, event, ui) {
    if (ui.item.hasOwnProperty('title')) {
      $('form.linkit-editor-dialog-form').find(fieldName).val(ui.item.title);
    }
  });

})(jQuery, Drupal, document);
