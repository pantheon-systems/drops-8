/**
 * @file
 * JavaScript behaviors for composite element builder.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Initialize composite element builder.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformElementComposite = {
    attach: function (context) {
      $('[data-composite-types]').once('webform-composite-types').each(function () {
        var $element = $(this);
        var $type = $element.closest('tr').find('.js-webform-composite-type');

        var types = $element.attr('data-composite-types').split(',');
        var required = $element.attr('data-composite-required');

        $type.on('change', function () {
          if ($.inArray($(this).val(), types) === -1) {
            $element.hide();
            if (required) {
              $element.removeAttr('required aria-required');
            }
          }
          else {
            $element.show();
            if (required) {
              $element.attr({'required': 'required', 'aria-required': 'true'});
            }
          }
        }).change();
      });
    }
  };

})(jQuery, Drupal);
