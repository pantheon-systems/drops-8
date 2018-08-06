(function ($) {

  'use strict';

  Drupal.FieldGroup = Drupal.FieldGroup || {};
  Drupal.FieldGroup.Effects = Drupal.FieldGroup.Effects || {};

  /**
   * Implements Drupal.FieldGroup.processHook().
   */
  Drupal.FieldGroup.Effects.processAccordion = {
    execute: function (context, settings, group_info) {
      $('div.field-group-accordion-wrapper', context).once('fieldgroup-effects').each(function () {
        var wrapper = $(this);

        // Get the index to set active.
        var active_index = false;
        wrapper.find('.accordion-item').each(function (i) {
          if ($(this).hasClass('field-group-accordion-active')) {
            active_index = i;
          }
        });

        wrapper.accordion({
          heightStyle: 'content',
          active: active_index,
          collapsible: true,
          changestart: function (event, ui) {
            if ($(this).hasClass('effect-none')) {
              ui.options.animated = false;
            }
            else {
              ui.options.animated = 'slide';
            }
          }
        });

        if (group_info.context === 'form') {

          var $firstErrorItem = false;

          // Add required fields mark to any element containing required fields
          wrapper.find('div.field-group-accordion-item').each(function (i) {

            var $this = $(this);
            if ($this.is('.required-fields') && ($this.find('[required]').length > 0 || $this.find('.form-required').length > 0)) {
              $('h3.ui-accordion-header a').eq(i).addClass('form-required');
            }
            if ($('.error', $this).length) {
              // Save first error item, for focussing it.
              if (!$firstErrorItem) {
                $firstErrorItem = $this.parent().accordion('activate', i);
              }
              $('h3.ui-accordion-header').eq(i).addClass('error');
            }
          });

          // Save first error item, for focussing it.
          if (!$firstErrorItem) {
            $('.ui-accordion-content-active', $firstErrorItem).css({height: 'auto', width: 'auto', display: 'block'});
          }

        }
      });
    }
  };

})(jQuery);
