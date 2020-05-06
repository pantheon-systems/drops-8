/**
 * @file
 * JavaScript behaviors to fix jQuery UI dialogs.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Ensure that ckeditor has focus when displayed inside of jquery-ui dialog widget
   *
   * @see http://stackoverflow.com/questions/20533487/how-to-ensure-that-ckeditor-has-focus-when-displayed-inside-of-jquery-ui-dialog
   */
  var _allowInteraction = $.ui.dialog.prototype._allowInteraction;
  $.ui.dialog.prototype._allowInteraction = function (event) {
    if ($(event.target).closest('.cke_dialog').length) {
      return true;
    }
    return _allowInteraction.apply(this, arguments);
  };

  /**
   * Attaches webform dialog behaviors.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches event listeners for webform dialogs.
   */
  Drupal.behaviors.webformDialogEvents = {
    attach: function () {
      $(window).once('webform-dialog').on({
        'dialog:aftercreate': function (event, dialog, $element, settings) {
          setTimeout(function () {
            var hasFocus = $element.find('[autofocus]:tabbable');
            if (!hasFocus.length) {
              // Move focus to first input which is not a button.
              hasFocus = $element.find(':input:tabbable:not(:button)');
            }
            if (!hasFocus.length) {
              // Move focus to close dialog button.
              hasFocus = $element.parent().find('.ui-dialog-titlebar-close');
            }
            hasFocus.eq(0).focus();
          });
        }
      });
    }
  };

})(jQuery, Drupal);
