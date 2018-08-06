/**
 * @file
 * Javascript for the media bundle form.
 */

(function ($, Drupal) {
  'use strict';

  /**
   * Behaviors for setting summaries on media bundle form.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behaviors on media bundle edit forms.
   */
  Drupal.behaviors.contentTypes = {
    attach: function (context) {
      var $context = $(context);
      // Provide the vertical tab summaries.
      $context.find('#edit-workflow').drupalSetSummary(function (context) {
        var vals = [];
        $(context).find('input[name^="options"]:checked').parent().each(function () {
          vals.push(Drupal.checkPlain($(this).find('label').text()));
        });
        if (!$(context).find('#edit-options-status').is(':checked')) {
          vals.unshift(Drupal.t('Not published'));
        }
        return vals.join(', ');
      });
      $('#edit-language', context).drupalSetSummary(function (context) {
        var vals = [];

        vals.push($('.js-form-item-language-configuration-langcode select option:selected', context).text());

        $('input:checked', context).next('label').each(function () {
          vals.push(Drupal.checkPlain($(this).text()));
        });

        return vals.join(', ');
      });
    }
  };

})(jQuery, Drupal);
