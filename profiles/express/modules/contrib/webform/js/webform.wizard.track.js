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
      // Note: window.history is only supported by IE 10+.
      if (window.history && window.history.replaceState) {
        if ($(context).hasData('webform-wizard-current-page')) {
          trackPage(context);
        }
      }

      // When paging next and back update the URL so that Drupal knows what
      // the expected page name or index is going to be.
      // NOTE: If conditional wizard page logic is configured the
      // expected page name or index may not be accurate.
      $(':button[data-webform-wizard-page], :submit[data-webform-wizard-page]', context).once('webform-wizard-page').on('click', function () {
        var page = $(this).attr('data-webform-wizard-page');
        this.form.action = setUrlPageParameter(this.form.action, page);
      });

      /**
       * Append the form's current page data attribute to the browser's URL.
       *
       * @param {HTMLElement} form
       *   The form element.
       */
      function trackPage(form) {
        var $form = $(form);
        // Make sure the form is visible before updating the URL.
        if ($form.is(':visible')) {
          var page = $form.attr('data-webform-wizard-current-page');
          var url = setUrlPageParameter(window.location.toString(), page);
          window.history.replaceState(null, null, url);
        }
      }

      /**
       * Set URLs page parameter.
       *
       * @param {string} url
       *   A url.
       * @param {string} page
       *   The current page.
       *
       * @return {string}
       *   A URL with page parameter.
       */
      function setUrlPageParameter(url, page) {
        var regex = /([?&])page=[^?&]+/;
        if (url.match(regex)) {
          return url.replace(regex, '$1page=' + page);
        }
        else {
          return url + (url.indexOf('?') !== -1 ? '&page=' : '?page=') + page;
        }
      }

    }
  };

})(jQuery, Drupal);
