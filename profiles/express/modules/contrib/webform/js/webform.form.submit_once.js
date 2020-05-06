/**
 * @file
 * JavaScript behaviors for preventing duplicate webform submissions.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Submit once.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for preventing duplicate webform submissions.
   */
  Drupal.behaviors.webformSubmitOnce = {
    attach: function (context) {
      $('.js-webform-submit-once', context).each(function () {
        var $form = $(this);
        // Remove data-webform-submitted.
        $form.removeData('webform-submitted');
        // Remove .js-webform-submit-clicked.
        $form.find('.js-webform-wizard-pages-links :submit, .form-actions :submit').removeClass('js-webform-submit-clicked');

        // Track which submit button was clicked.
        // @see http://stackoverflow.com/questions/5721724/jquery-how-to-get-which-button-was-clicked-upon-form-submission
        $form.find('.js-webform-wizard-pages-links :submit, .form-actions :submit').click(function () {
          $form.find('.js-webform-wizard-pages-links :submit, .form-actions :submit')
            .removeClass('js-webform-submit-clicked');
          $(this)
            .addClass('js-webform-submit-clicked');
        });

        $(this).submit(function () {
          // Find clicked button
          var $clickedButton = $form.find('.js-webform-wizard-pages-links :submit.js-webform-submit-clicked, .form-actions :submit.js-webform-submit-clicked');

          // Don't submit if client-side validation has failed.
          if (!$clickedButton.attr('formnovalidate') && $.isFunction(jQuery.fn.valid) && !($form.valid())) {
            return false;
          }

          // Track webform submitted.
          if ($form.data('webform-submitted')) {
            return false;
          }
          $form.data('webform-submitted', 'true');

          // Visually disable all submit buttons.
          // Submit buttons can't disabled because their op(eration) must to be posted back to the server.
          $form.find('.js-webform-wizard-pages-links :submit, .form-actions :submit').addClass('is-disabled');

          // Set the throbber progress indicator.
          // @see Drupal.Ajax.prototype.setProgressIndicatorThrobber
          var $progress = $('<div class="ajax-progress ajax-progress-throbber"><div class="throbber">&nbsp;</div></div>');
          $clickedButton.after($progress);
        });
      });
    }
  };

})(jQuery, Drupal);
