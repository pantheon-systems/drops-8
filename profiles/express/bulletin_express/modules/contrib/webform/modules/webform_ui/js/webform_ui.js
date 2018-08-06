/**
 * @file
 * Javascript behaviors for Webform UI.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Highlights the element that was just updated.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the element update.
   *
   * @see Drupal.behaviors.blockHighlightPlacement
   */
  Drupal.behaviors.webformUiElementsUpdate = {
    attach: function (context, settings) {
      if (settings.webformUiElementUpdate) {
        $(context).find('[data-drupal-selector="edit-webform-ui-elements"]').once('webform-ui-elements-update').each(function () {
          var $container = $(this);

          // If the element is visible, don't scroll to it.
          // @see http://stackoverflow.com/questions/487073/check-if-element-is-visible-after-scrolling;
          var $element = $('.js-webform-ui-element-update');
          var elementTop = $element.offset().top;
          var elementBottom = elementTop + $element.height();
          var isVisible = (elementTop >= 0) && (elementBottom <= window.innerHeight);
          if (isVisible) {
            return;
          }

          // Just scrolling the document.body will not work in Firefox. The html
          // element is needed as well.
          $('html, body').animate({
            scrollTop: $('.js-webform-ui-element-update').offset().top - $container.offset().top + $container.scrollTop()
          }, 500);
        });
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
