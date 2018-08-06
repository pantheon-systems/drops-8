/**
 * @file
 * IMCE integration for Linkit.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * @namespace
   *
   * Need to be in the global namespace, otherwise the IMCE window will not show
   * the 'select' button in the toolbar.
   */
  var linkitImce = window.linkitImce = {};

  /**
   * Drupal behavior to handle imce linkit integration.
   */
  Drupal.behaviors.linkitImce = {
    attach: function (context, settings) {
      var $link = $(context).find('.linkit-imce-open').once('linkit-imce-open');
      if ($link.length) {
        $link.bind('click', function (event) {
          event.preventDefault();
          window.open($(this).attr('href'), '', 'width=760,height=560,resizable=1');
        });
      }
    }
  };

  /**
   * Handler for imce sendto operation.
   */
  linkitImce.sendto = function (file, win) {
    var imce = win.imce;
    var items = imce.getSelection();

    if (imce.countSelection() > 1) {
      imce.setMessage(Drupal.t('You can only select one file.'));
      return;
    }
    var path = imce.getConf('root_url') + '/' + imce.getItemPath(items[0]);
    $('[data-drupal-selector="edit-attributes-href"]').val(path);
    win.close();
  };


})(jQuery, Drupal, drupalSettings);


