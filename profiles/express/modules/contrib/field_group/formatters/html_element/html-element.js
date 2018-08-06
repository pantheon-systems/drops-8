(function ($) {

  'use strict';

  Drupal.FieldGroup = Drupal.FieldGroup || {};
  Drupal.FieldGroup.Effects = Drupal.FieldGroup.Effects || {};

  /**
   * Implements Drupal.FieldGroup.processHook().
   */
  Drupal.FieldGroup.Effects.processHtml_element = {
    execute: function (context, settings, group_info) {

      $('.field-group-html-element', context).once('fieldgroup-effects').each(function () {
        var $wrapper = $(this);

        if ($wrapper.hasClass('fieldgroup-collapsible')) {
          Drupal.FieldGroup.Effects.processHtml_element.renderCollapsible($wrapper);
        }
        else {

          // Add required field markers if needed
          if (group_info.settings.show_label && $wrapper.is('.required-fields') && ($wrapper.find('[required]').length > 0 || $wrapper.find('.form-required').length > 0)) {
            $wrapper.find(group_info.settings.label_element + ':first').addClass('form-required');
          }
        }

      });
    },
    renderCollapsible: function($wrapper) {

      // Turn the legend into a clickable link, but retain span.field-group-format-toggler
      // for CSS positioning.

      var $toggler = $('.field-group-toggler:first', $wrapper);
      var $link = $('<a class="field-group-title" href="#"></a>');
      $link.prepend($toggler.contents());

      // Add required field markers if needed
      if ($wrapper.is('.required-fields') && ($wrapper.find('[required]').length > 0 || $wrapper.find('.form-required').length > 0)) {
        $link.addClass('form-required');
      }

      $link.appendTo($toggler);

      // .wrapInner() does not retain bound events.
      $link.click(function () {
        var wrapper = $wrapper.get(0);
        // Don't animate multiple times.
        if (!wrapper.animating) {
          wrapper.animating = true;
          var speed = $wrapper.hasClass('speed-fast') ? 300 : 1000;
          if ($wrapper.hasClass('effect-none') && $wrapper.hasClass('speed-none')) {
            $('> .field-group-wrapper', wrapper).toggle();
          }
          else if ($wrapper.hasClass('effect-blind')) {
            $('> .field-group-wrapper', wrapper).toggle('blind', {}, speed);
          }
          else {
            $('> .field-group-wrapper', wrapper).toggle(speed);
          }
          wrapper.animating = false;
        }
        $wrapper.toggleClass('collapsed');
        return false;
      });
    }
  };

})(jQuery);
