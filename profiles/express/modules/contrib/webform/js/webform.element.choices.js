/**
 * @file
 * JavaScript behaviors for Choices integration.
 */

(function ($, Drupal) {

  'use strict';

  // @see https://github.com/jshjohnson/Choices
  Drupal.webform = Drupal.webform || {};
  Drupal.webform.choices = Drupal.webform.choices || {};
  Drupal.webform.choices.options = Drupal.webform.choices.options || {};
  Drupal.webform.choices.options.selectSearchMinItems = 10;

  /**
   * Initialize Choices support.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformChoices = {
    attach: function (context) {
      if (!window.Choices) {
        return;
      }

      $(context)
        .find('select.js-webform-choices, .js-webform-choices select')
        .once('webform-choices')
        .each(function () {
          var $select = $(this);
          var options = {
            // Disable sorting.
            shouldSort: false,
            // Translate all default strings.
            loadingText: Drupal.t('Loading...'),
            noResultsText: Drupal.t('No results found'),
            noChoicesText: Drupal.t('No choices to choose from'),
            itemSelectText: Drupal.t('Press to select'),
            addItemText: function addItemText(value) {
              return Drupal.t(
                'Press Enter to add <b>@value</b>',
                {'@value': value}
              );
            },
            maxItemText: function maxItemText(maxItemCount) {
              return Drupal.t(
                'Only @max value can be added',
                {'@max': maxItemCount}
              );
            }
          };

          // Enabling the 'remove item buttons' options addresses accessibility
          // issue when deleting multiple options.
          if ($select.attr('multiple')) {
            options.removeItemButton = true;
          }

          options = $.extend(options, Drupal.webform.choices.options);

          if ($select.data('placeholder')) {
            options.placeholder = true;
            options.placeholderValue = $select.data('placeholder');
          }
          if ($select.data('limit')) {
            options.maxItemCount = $select.data('limit');
          }

          var choices = new Choices(this, options);

          // Store reference to this element's choices instance so that
          // it can be enabled or disabled.
          $(this).data('choices', choices);
        });
    }
  };

  var $document = $(document);

  // Refresh choices (select) widgets when they are disabled/enabled.
  $document.on('state:disabled', function (e) {
    var $choices = $(e.target).find('.js-webform-choices');
    if ($(e.target).hasClass('js-webform-choices')) {
      $choices.add(e.target);
    }
    $choices.each(function () {
      var choices = $(this).data('choices');
      if (choices) {
        choices[(e.value) ? 'disable' : 'enable']();
      }
    });
  });

})(jQuery, Drupal);
