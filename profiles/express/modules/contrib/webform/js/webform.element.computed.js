/**
 * @file
 * JavaScript behaviors for computed elements.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.webform = Drupal.webform || {};
  Drupal.webform.computed = Drupal.webform.computed || {};
  Drupal.webform.computed.delay = Drupal.webform.computed.delay || 500;

  var computedElements = [];

  /**
   * Initialize computed elements.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformComputed = {
    attach: function (context) {
      // Find computed elements and build trigger selectors.
      $(context).find('.js-webform-computed').once('webform-computed').each(function () {
        // Get computed element and form.
        var $element = $(this);
        var $form = $element.closest('form');

        // Get unique id for computed element based on the element name
        // and form id.
        var id = $form.attr('id') + '-' + $element.find('input[type="hidden"]').attr('name');

        // Get elements that are used by the computed element.
        var elementKeys = $(this).data('webform-element-keys').split(',');
        if (!elementKeys) {
          return;
        }

        // Get computed element trigger selectors.
        var inputs = [];
        $.each(elementKeys, function (i, key) {
          // Exact input match.
          inputs.push(':input[name="' + key + '"]');
          // Sub inputs. (aka #tree)
          inputs.push(':input[name^="' + key + '["]');
        });
        var triggers = inputs.join(',');

        // Track computed elements.
        computedElements.push({
          id: id,
          element: $element,
          form: $form,
          triggers: triggers
        });

        // Clear computed last values to ensure that a computed element is
        // always re-computed on page load.
        $element.attr('data-webform-computed-last', '');
      });

      // Initialize triggers for each computed element.
      $.each(computedElements, function (index, computedElement) {
        // Get trigger from the current context.
        var $triggers = $(context).find(computedElement.triggers);
        // Make sure current context has triggers.
        if (!$triggers.length) {
          return;
        }

        // Make sure triggers are within the computed element's form
        // and only initialized once.
        $triggers = computedElement.form.find($triggers)
          .once('webform-computed-triggers-' + computedElement.id);
        // Double check that there are triggers which need to be initialized.
        if (!$triggers.length) {
          return;
        }

        initializeTriggers(computedElement.element, $triggers);
      });

      /**
       * Initialize computed element triggers.
       *
       * @param {jQuery} $element
       *   An jQuery object containing the computed element.
       * @param {jQuery} $triggers
       *   An jQuery object containing the computed element triggers.
       */
      function initializeTriggers($element, $triggers) {
        // Add event handler to computed element triggers.
        $triggers.on('keyup change', queueUpdate);

        // Add event handler to computed element tabledrag.
        var $draggable = $triggers.closest('tr.draggable');
        if ($draggable.length) {
          $draggable.find('.tabledrag-handle').on('mouseup pointerup touchend',
            queueUpdate);
        }

        // Queue an update to make sure trigger values are computed.
        queueUpdate();

        // Queue computed element updates using a timer.
        var timer = null;
        function queueUpdate() {
          if (timer) {
            window.clearTimeout(timer);
            timer = null;
          }
          timer = window.setTimeout(triggerUpdate, Drupal.webform.computed.delay);
        }

        function triggerUpdate() {
          // Get computed element wrapper.
          var $wrapper = $element.find('.js-webform-computed-wrapper');

          // If computed element is loading, requeue the update and wait for
          // the computed element to be updated.
          if ($wrapper.hasClass('webform-computed-loading')) {
            queueUpdate();
            return;
          }

          // Prevent duplicate computations.
          // @see Drupal.behaviors.formSingleSubmit
          var formValues = $triggers.serialize();
          var previousValues = $element.attr('data-webform-computed-last');
          if (previousValues === formValues) {
            return;
          }
          $element.attr('data-webform-computed-last', formValues);

          // Add loading class to computed wrapper.
          $wrapper.addClass('webform-computed-loading');

          // Trigger computation.
          $element.find('.js-form-submit').mousedown();
        }
      }
    }
  };

})(jQuery, Drupal);
