/**
 * @file
 * JavaScript behaviors for managed file uploads.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Track file uploads and display confirm dialog when an file upload is in progress.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformManagedFileAutoUpload = {
    attach: function attach(context) {
      // Add submit handler to file upload form.
      $(context).find('form')
        .once('webform-auto-file-upload')
        .on('submit', function (event) {
          var $form = $(this);
          if ($form.data('webform-auto-file-uploads') > 0 && blockSubmit($form)) {
            event.preventDefault();
            return false;
          }
          return true;
        });

      // Add submit handler to form.beforeSend.
      // Update Drupal.Ajax.prototype.beforeSend only once.
      if (typeof Drupal.Ajax !== 'undefined' && typeof Drupal.Ajax.prototype.beforeSubmitWebformManagedFileAutoUploadOriginal === 'undefined') {
        Drupal.Ajax.prototype.beforeSubmitWebformManagedFileAutoUploadOriginal = Drupal.Ajax.prototype.beforeSubmit;
        Drupal.Ajax.prototype.beforeSubmit = function (form_values, element_settings, options) {
          var $form = this.$form;
          var $element = $(this.element);

          // Determine if the triggering element is within .form-actions.
          var isFormActions = $element
            .closest('.form-actions').length;

          // Determine if the triggering element is within a multiple element.
          var isMultipleUpload = $element
            .parents('.js-form-type-webform-multiple, .js-form-type-webform-custom-composite')
            .find('.js-form-managed-file').length;

          // Determine if the triggering element is not within a
          // managed file element.
          var isManagedUploadButton = $element.parents('.js-form-managed-file').length;

          // Only trigger block submit for .form-actions and multiple element
          // with file upload.
          if ($form.data('webform-auto-file-uploads') > 0 &&
            (isFormActions || (isMultipleUpload && !isManagedUploadButton)) &&
            blockSubmit($form)) {
            this.ajaxing = false;
            return false;
          }
          return this.beforeSubmitWebformManagedFileAutoUploadOriginal.apply(this, arguments);
        };
      }

      $(context).find('input[type="file"]').once('webform-auto-file-upload').on('change', function () {
        // Track file upload.
        $(this).data('webform-auto-file-upload', true);

        // Increment form file uploads.
        var $form = $(this.form);
        var fileUploads = ($form.data('webform-auto-file-uploads') || 0);
        $form.data('webform-auto-file-uploads', fileUploads + 1);
      });
    },
    detach: function detach(context, settings, trigger) {
      if (trigger === 'unload') {
        $(context).find('input[type="file"]').removeOnce('webform-auto-file-upload').each(function () {
          if ($(this).data('webform-auto-file-upload')) {
            // Remove file upload tracking.
            $(this).removeData('webform-auto-file-upload');

            // Decrease form file uploads.
            var $form = $(this.form);
            var fileUploads = ($form.data('webform-auto-file-uploads') || 0);
            $form.data('webform-auto-file-uploads', fileUploads - 1);
          }
        });
      }
    }
  };

  /**
   * Block form submit.
   *
   * @param {jQuery} form
   *   A form.
   *
   * @return {boolean}
   *   TRUE if form submit should be blocked.
   */
  function blockSubmit(form) {
    if ($(form).data('webform-auto-file-uploads') < 0) {
      return false;
    }

    var message = Drupal.t('File upload in progress. Uploaded file may be lost.') +
      '\n' +
      Drupal.t('Do you want to continue?');
    return !window.confirm(message);
  }

})(jQuery, Drupal);
