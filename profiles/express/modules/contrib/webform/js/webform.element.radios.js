/**
 * @file
 * JavaScript behaviors for radio buttons.
 *
 * Fix #states and #required for radios buttons.
 *
 * @see Issue #2856795: If radio buttons are required but not filled form is nevertheless submitted.
 * @see Issue #2856315: Conditional Logic - Requiring Radios in a Fieldset.
 * @see Issue #2731991: Setting required on radios marks all options required.
 * @see css/webform.form.css
 * @see /core/misc/states.js
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Attach handler to add .js-webform-radios-fieldset to radios wrapper fieldset.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformRadios = {
    attach: function (context) {
      $('.js-webform-radios', context).closest('fieldset.form-composite').addClass('js-webform-radios-fieldset');
    }
  };

  // Make absolutely sure the below event handlers are triggered after
  // the /core/misc/states.js event handlers by attaching them after DOM load.
  $(function () {
    Drupal.behaviors.webformRadios.attach($(document));

    function setRequired($target, required) {
      if (!$target.hasClass('js-webform-radios-fieldset') && !$target.hasClass('js-webform-radios-other')) {
        return;
      }

      if (required) {
        $target.find('input[type="radio"]').attr({'required': 'required', 'aria-required': 'aria-required'});
        $target.find('legend span').addClass('js-form-required form-required');
      }
      else {
        $target.find('input[type="radio"]').removeAttr('required aria-required');
        $target.find('legend span').removeClass('js-form-required form-required');
      }
    }

    $('.js-webform-radios-fieldset[required="required"], .js-form-type-webform-radios-other[required="required"]').each(function() {
      setRequired($(this), true);
    });

    $(document).on('state:required', function (e) {
      if (e.trigger) {
        setRequired($(e.target), e.value);
      }
    });
  });

})(jQuery, Drupal);
