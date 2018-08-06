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
        $form.removeAttr('webform-submitted');
        $form.find('#edit-actions :submit').removeAttr('webform-clicked');

        // Track which submit button was clicked.
        // @see http://stackoverflow.com/questions/5721724/jquery-how-to-get-which-button-was-clicked-upon-form-submission
        $form.find('#edit-actions :submit').click(function () {
          $form.find('#edit-actions :submit').removeAttr('webform-clicked');
          $(this).attr('webform-clicked', 'true');
        });

        $(this).submit(function () {
          // Don't submit if client-side validation has failed.
          if ($.isFunction(jQuery.fn.valid) && !($form.valid())) {
            return false;
          }

          // Track webform submitted.
          if ($form.attr('webform-submitted')) {
            return false;
          }
          $form.attr('webform-submitted', 'true');

          // Visually disable all submit buttons.
          // Submit buttons can't disabled because their op(eration) must to be posted back to the server.
          $form.find('#edit-actions :submit').addClass('is-disabled');

          // Set the throbber progress indicator.
          // @see Drupal.Ajax.prototype.setProgressIndicatorThrobber
          var $clickedButton = $form.find('#edit-actions input[type=submit][webform-clicked=true]');
          var $progress = $('<div class="ajax-progress ajax-progress-throbber"><div class="throbber">&nbsp;</div></div>');
          $clickedButton.after($progress);
        });
      });
    }
  };

})(jQuery, Drupal);
