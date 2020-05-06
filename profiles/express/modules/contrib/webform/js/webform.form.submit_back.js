/**
 * @file
 * JavaScript to allow back button submit wizard page.
 */

(function ($) {

  'use strict';

  // From: https://stackoverflow.com/a/39019647
  if (window.history && window.history.pushState) {
    window.history.pushState('', null, '');
    window.onpopstate = function (event) {
      $('#edit-wizard-prev, #edit-preview-prev, .webform-button--previous')
        .slice(0, 1)
        .click();
    };
  }

})(jQuery);
