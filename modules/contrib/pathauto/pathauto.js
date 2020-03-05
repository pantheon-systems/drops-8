(function ($) {
  'use strict';
  Drupal.behaviors.pathFieldsetSummaries = {
    attach: function (context) {
      $(context).find('.path-form').drupalSetSummary(function (context) {
        var path = $('.js-form-item-path-0-alias input', context).val();
        var automatic = $('.js-form-item-path-0-pathauto input', context).prop('checked');

        if (automatic) {
          return Drupal.t('Automatic alias');
        }
        else if (path) {
          return Drupal.t('Alias: @alias', {'@alias': path});
        }
        else {
          return Drupal.t('No alias');
        }
      });
    }
  };
})(jQuery);
