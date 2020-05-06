/**
 * @file
 * JavaScript behaviors for form tabs using jQuery UI.
 */

(function ($, Drupal) {

  'use strict';

  // @see http://api.jqueryui.com/tabs/
  Drupal.webform = Drupal.webform || {};
  Drupal.webform.formTabs = Drupal.webform.formTabs || {};
  Drupal.webform.formTabs.options = Drupal.webform.formTabs.options || {
    hide: true,
    show: true
  };

  /**
   * Initialize webform tabs.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for form tabs using jQuery UI.
   *
   * @see \Drupal\webform\Utility\WebformFormHelper::buildTabs
   */
  Drupal.behaviors.webformFormTabs = {
    attach: function (context) {
      $(context).find('div.webform-tabs').once('webform-tabs').each(function () {
        var $tabs = $(this);
        var options = jQuery.extend({}, Drupal.webform.formTabs.options);

        // Set active tab and clear the location hash once it is set.
        if (location.hash) {
          var active = $('a[href="' + location.hash + '"]').data('tab-index');
          if (typeof active !== 'undefined') {
            options.active = active;
            location.hash = '';
          }
        }

        $tabs.tabs(options);
      });
    }
  };

})(jQuery, Drupal);
