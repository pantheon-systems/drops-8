/**
 * @file
 * JavaScript behaviors for webform wizard.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Tracks the wizard's current page in the URL.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Tracks the wizard's current page in the URL.
   */
  Drupal.behaviors.webformWizardTrackPage = {
    attach: function (context) {
      // Make sure on page load or Ajax refresh the location ?page is correct
      // since conditional logic can skip pages.
      // Note: window.history is only supported by IE 10+ and all other browsers.
      if (window.history && history.replaceState) {
        $('form[data-webform-wizard-current-page]', context).once('webform-wizard-current-page').each(function () {
          var page = $(this).attr('data-webform-wizard-current-page');
          history.replaceState(null, null, window.location.toString().replace(/\?.+$/, '') + '?page=' + page);
        });
      }

      // When paging next and back update the URL so that Drupal knows what
      // the expected page name or index is going to be.
      // NOTE: If conditional wizard page logic is configured the
      // expected page name or index may not be accurate.
      $(':button[data-webform-wizard-page], :submit[data-webform-wizard-page]', context).once('webform-wizard-page').on('click', function() {
        var page = $(this).attr('data-webform-wizard-page');
        this.form.action = this.form.action.replace(/\?.+$/, '') + '?page=' + page;
      });
    }
  };


})(jQuery, Drupal);
