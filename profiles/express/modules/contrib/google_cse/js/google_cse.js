/**
 * @file
 * Adds Google Custom Search Watermark.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.googleCSECustomSearch = {
    attach: function (context, settings) {
      var getWatermarkBackground = function (value) {
        var googleCSEBaseUrl = 'https://www.google.com/cse/intl/';
        var googleCSEImageUrl = 'images/google_custom_search_watermark.gif';
        var language = drupalSettings.googleCSE.language + '/';
        return value ? '' : ' url(' + googleCSEBaseUrl + language + googleCSEImageUrl + ') left no-repeat';
      };
      var onFocus = function (e) {
        $(e.target).css('background', '#ffffff');
      };
      var onBlur = function (e) {
        $(e.target).css('background', '#ffffff' + getWatermarkBackground($(e.target).val()));
      };
      var googleCSEWatermark = function (context, query) {
        var form = jQuery(context);
        var searchInputs = $('[data-drupal-selector="' + query + '"]', form);
        if (navigator.platform === 'Win32') {
          searchInputs.css('style', 'border: 1px solid #7e9db9; padding: 2px;');
        }
        searchInputs.blur(onBlur);
        searchInputs.focus(onFocus);
        searchInputs.each(function () {
          var event = {};
          event.target = this;
          onBlur(event);
        });
      };

      googleCSEWatermark('[data-drupal-selector="search-block-form"] [data-drupal-form-fields="edit-keys--2"]', 'edit-keys');
      googleCSEWatermark('[data-drupal-selector="search-block-form"] [data-drupal-form-fields="edit-keys"]', 'edit-keys');
      googleCSEWatermark('[data-drupal-selector="search-form"]', 'edit-keys');
      googleCSEWatermark('[data-drupal-selector="google-cse-search-box-form"]', 'edit-query');
    }
  };
})(jQuery, Drupal, drupalSettings);
