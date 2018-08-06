/**
 * @file entity_browser.modal.js
 *
 * Defines the behavior of the entity browser's modal display.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.entityBrowserModal = {};

  Drupal.AjaxCommands.prototype.select_entities = function (ajax, response, status) {
    var uuid = drupalSettings.entity_browser.modal.uuid;

    $(':input[data-uuid="' + uuid + '"]').trigger('entities-selected', [uuid, response.entities])
      .removeClass('entity-browser-processed').unbind('entities-selected');
  };

  /**
   * Registers behaviours related to modal display.
   */
  Drupal.behaviors.entityBrowserModal = {
    attach: function (context) {
      _.each(drupalSettings.entity_browser.modal, function (instance) {
        _.each(instance.js_callbacks, function (callback) {
          // Get the callback.
          callback = callback.split('.');
          var fn = window;

          for (var j = 0; j < callback.length; j++) {
            fn = fn[callback[j]];
          }

          if (typeof fn === 'function') {
            $(':input[data-uuid="' + instance.uuid + '"]').not('.entity-browser-processed')
              .bind('entities-selected', fn).addClass('entity-browser-processed');
          }
        });
        if (instance.auto_open) {
          $('input[data-uuid="' + instance.uuid + '"]').click();
        }
      });
    }
  };

  /**
   * Registers behaviours related to modal open and windows resize for fluid modal.
   */
  Drupal.behaviors.fluidModal = {
    attach: function (context) {

      // Recalculate dialog size on window resize.
      $(window).resize(function (context) {
        Drupal.entityBrowserModal.fluidDialog();
      });

      // Catch dialog if opened within a viewport smaller than the dialog width
      // and recalculate size of all open dialogs.
      $(document).on('dialogopen', '.ui-dialog', function (event, ui) {
        Drupal.entityBrowserModal.fluidDialog();
      });

      // Disable scrolling of the whole browser window to not interfere with the
      // iframe scrollbar.
      $(window).on({
        'dialog:aftercreate': function (event, dialog, $element, settings) {
          $('body').css({overflow: 'hidden'});
        },
        'dialog:beforeclose': function (event, dialog, $element) {
          $('body').css({overflow: 'inherit'});
        }
      });
    }
  };

  /**
   * Recalculates size of the modal.
   */
  Drupal.entityBrowserModal.fluidDialog = function () {

    var $visible = $('.ui-dialog:visible');
    // For each open dialog.
    $visible.each(function () {
      var $this = $(this);
      var dialog = $this.find('.ui-dialog-content').data('ui-dialog');
      // If fluid option == true.
      if (dialog.options.fluid) {
        var wWidth = $(window).width();
        // Check window width against dialog width.
        if (dialog.options.maxWidth && (wWidth > parseInt(dialog.options.maxWidth) + 50)) {
          dialog.option('width', dialog.options.maxWidth);
        }
        else {
          // If no maxWidth is defined, make it responsive.
          dialog.option('width', '92%');
        }

        var vHeight = $(window).height();
        // Check window width against dialog width.
        if (dialog.options.maxHeight && vHeight > (parseInt(dialog.options.maxHeight) + 50)) {
          dialog.option('height', dialog.options.maxHeight);
        }
        else {
          // If no maxHeight is defined, make it responsive.
          dialog.option('height', vHeight - 100);

          // Because there is no iframe height 100% in HTML 5, we have to set
          // the height of the iframe as well.
          var contentHeight = $this.find('.ui-dialog-content').height() - 20;
          $this.find('iframe').css('height', contentHeight);
        }

        // Reposition dialog.
        dialog.option('position', dialog.options.position);
      }
    });
  };

}(jQuery, Drupal, drupalSettings));
