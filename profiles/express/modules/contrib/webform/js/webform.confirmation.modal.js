/**
 * @file
 * JavaScript behaviors for confirmation modal.
 */

(function ($, Drupal) {

  'use strict';

  // @see http://api.jqueryui.com/dialog/
  Drupal.webform = Drupal.webform || {};
  Drupal.webform.confirmationModal = Drupal.webform.confirmationModal || {};
  Drupal.webform.confirmationModal.options = Drupal.webform.confirmationModal.options || {};

  /**
   * Display confirmation message in a modal.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformConfirmationModal = {
    attach: function (context) {
      $('.js-webform-confirmation-modal', context).once('webform-confirmation-modal').each(function () {
        var $element = $(this);

        var $dialog = $element.find('.webform-confirmation-modal--content');

        var options = {
          dialogClass: 'webform-confirmation-modal',
          minWidth: 600,
          resizable: false,
          title: $element.find('.webform-confirmation-modal--title').text(),
          close: function (event) {
            Drupal.dialog(event.target).close();
            Drupal.detachBehaviors(event.target, null, 'unload');
            $(event.target).remove();
          }
        };

        options = $.extend(options, Drupal.webform.confirmationModal.options);

        var dialog = Drupal.dialog($dialog, options);

        // Use setTimeout to prevent dialog.position.js
        // Uncaught TypeError: Cannot read property 'settings' of undefined
        setTimeout(function () {
          dialog.showModal();

          // Close any open webform submission modals.
          var $modal = $('#drupal-modal');
          if ($modal.find('.webform-submission-form').length) {
            Drupal.dialog($modal .get(0)).close();
          }
        }, 1);
      });
    }
  };

})(jQuery, Drupal);
