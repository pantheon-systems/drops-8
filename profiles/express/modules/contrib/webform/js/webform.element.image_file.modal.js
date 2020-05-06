/**
 * @file
 * JavaScript behaviors for webform_image_file modal.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Display webform image file in a modal.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformImageFileModal = {
    attach: function (context) {
      $('.js-webform-image-file-modal', context).once('webform-image-file-modal').on('click', function () {
        // http://stackoverflow.com/questions/11442712/get-width-height-of-remote-image-from-url
        var img = new Image();
        img.src = $(this).attr('href');
        img.onload = function () {
          $('<div><img src="' + this.src + '" style="display: block; margin: 0 auto" /></div>').dialog({
            dialogClass: 'webform-image-file-modal-dialog',
            width: this.width + 60,
            height: this.height + 100,
            resizable: false,
            modal: true
          }).dialog('open');
        };
        return false;
      });
    }
  };

})(jQuery, Drupal);
