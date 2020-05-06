/**
 * @file
 * JavaScript behaviors for Bootstrap element help text (tooltip).
 *
 * @see js/webform.element.help.js
 */

(function ($, Drupal) {

  'use strict';

  // @see http://bootstrapdocs.com/v3.0.3/docs/javascript/#tooltips-usage
  Drupal.webformBootstrap = Drupal.webformBootstrap || {};
  Drupal.webformBootstrap.elementHelpIcon = Drupal.webformBootstrap.elementHelpIcon || {};
  Drupal.webformBootstrap.elementHelpIcon.options = Drupal.webformBootstrap.elementHelpIcon.options || {
    trigger: 'hover focus click',
    placement: 'auto right',
    delay: 200
  };

  /**
   * Bootstrap element help icon.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformBootstrapElementHelpIcon = {
    attach: function (context) {
      $(context).find('.webform-element-help').once('webform-element-help').each(function () {
        var $link = $(this);

        var options = $.extend({
          title: $link.attr('data-webform-help'),
          html: true
        }, Drupal.webformBootstrap.elementHelpIcon.options);

        $link.tooltip(options)
          .on('click', function (event) {
            // Prevent click from toggling <label>s wrapped around help.
            event.preventDefault();
          });
      });
    }
  };

})(jQuery, Drupal);
