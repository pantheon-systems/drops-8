/**
 * @file
 * JavaScript behaviors for webform custom options.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.webformOptionsCustom = Drupal.webformOptionsCustom || {};

  // @see http://api.jqueryui.com/tooltip/
  Drupal.webformOptionsCustom.jQueryUiTooltip = Drupal.webformOptionsCustom.jQueryUiTooltip || {};
  Drupal.webformOptionsCustom.jQueryUiTooltip.options = Drupal.webformOptionsCustom.jQueryUiTooltip.options || {
    tooltipClass: 'webform-options-custom-tooltip',
    track: true,
    // @see
    // https://stackoverflow.com/questions/18231315/jquery-ui-tooltip-html-with-links
    show: {delay: 300},
    close: function (event, ui) {
      ui.tooltip.hover(
        function () {
          $(this).stop(true).fadeTo(400, 1);
        },
        function () {
          $(this).fadeOut('400', function () {
            $(this).remove();
          });
        });
    }
  };

  // @see http://bootstrapdocs.com/v3.0.3/docs/javascript/#tooltips-usage
  Drupal.webformOptionsCustom.bootstrapTooltip = Drupal.webformOptionsCustom.bootstrapTooltip || {};
  Drupal.webformOptionsCustom.bootstrapTooltip.options = Drupal.webformOptionsCustom.bootstrapTooltip.options || {
    delay: 200
  };

  // @see https://github.com/ariutta/svg-pan-zoom
  Drupal.webformOptionsCustom.panAndZoom = Drupal.webformOptionsCustom.panAndZoom || {};
  Drupal.webformOptionsCustom.panAndZoom.options = Drupal.webformOptionsCustom.panAndZoom.options || {
    controlIconsEnabled: true,
    // Mouse event must be enable to allow keyboard accessibility to
    // continue to work.
    preventMouseEventsDefault: false,
    // Prevent scroll wheel zoom to allow users to scroll past the SVG graphic.
    mouseWheelZoomEnabled: false
  };

  /**
   * Custom options.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the block settings summaries.
   */
  Drupal.behaviors.webformOptionsCustom = {
    attach: function (context) {
      $('.js-webform-options-custom', context).once('webform-options-custom').each(function () {
        var $element = $(this);
        var $select = $element.find('select');
        var $template = $element.find('.webform-options-custom-template');
        var $svg = $template.children('svg');

        // Get select menu options.
        var descriptions = $element.attr('data-descriptions') ? JSON.parse($element.attr('data-descriptions')) : {};
        var selectOptions = {};
        $select.find('option').each(function () {
          selectOptions[this.value] = this;
          selectOptions[this.value].description = descriptions[this.value];
        });

        var hasMultiple = $select.is('[multiple]');
        var hasFill = $element.is('[data-fill]');
        var hasZoom = $element.is('[data-zoom]');
        var hasTooltip = $element.is('[data-tooltip]');
        var hasSelectHidden = $element.is('[data-select-hidden]');

        var $templateOptions = $template.find('[data-option-value]');
        var $focusableTemplateOptions = $templateOptions.not('text');

        // If select is hidden set its tabindex to -1 to prevent focus.
        if (hasSelectHidden) {
          $select.attr('tabindex', '-1');
        }

        // Initialize template options.
        $templateOptions.each(function () {
          var $templateOption = $(this);
          var value = $templateOption.attr('data-option-value');
          var option = selectOptions[value];

          // If select menu option is missing remove the
          // 'data-option-value' attribute.
          if (!option) {
            $templateOption.removeAttr('data-option-value');
            return;
          }

          initializeSelectOption(option);
          initializeTemplateOption($templateOption, option);
          initializeTemplateTooltip($templateOption, option);
        });

        // Pan and zoom.
        initializeZoom();

        // Select event handling.
        $select.on('change', setSelectValue);

        // Template event handling.
        $template
          .on('click', setTemplateValue)
          .on('keydown', function (event) {
            var $templateOption = $(event.target);
            if (!$templateOption.is('[data-option-value]')) {
              return;
            }

            // Space or return.
            if (event.which === 32 || event.which === 13) {
              setTemplateValue(event);
              event.preventDefault();
              return;
            }

            if (event.which >= 37 && event.which <= 40) {
              var $prev;
              var $next;
              $focusableTemplateOptions.each(function (index) {
                if (this === event.target) {
                  $prev = $focusableTemplateOptions[index - 1] ? $($focusableTemplateOptions[index - 1]) : null;
                  $next = $focusableTemplateOptions [index + 1] ? $($focusableTemplateOptions[index + 1]) : null;
                }
              });
              if (event.which === 37 || event.which === 38) {
                if ($prev) {
                  $prev.focus();
                }
              }
              else if (event.which === 39 || event.which === 40) {
                if ($next) {
                  $next.focus();
                }
              }
              event.preventDefault();
              return;
            }
          });

        setSelectValue();

        /* ****************************************************************** */
        /*  See select and template value callbacks. */
        /* ****************************************************************** */

        /**
         * Set select menu options value
         */
        function setSelectValue() {
          var values = (hasMultiple) ? $select.val() : [$select.val()];
          clearTemplateOptions();
          $(values).each(function (index, value) {
            $template.find('[data-option-value="' + value + '"]')
              .attr('aria-checked', 'true');
          });
          setTemplateTabIndex();
        }

        /**
         * Set template options value.
         *
         * @param {jQuery.Event} event
         *   The event triggered.
         */
        function setTemplateValue(event) {
          var $templateOption = $(event.target);
          if (!$templateOption.is('[data-option-value]')) {
            $templateOption = $templateOption.parents('[data-option-value]');
          }
          if ($templateOption.is('[data-option-value]')) {
            setValue($templateOption.attr('data-option-value'));
            if ($templateOption.is('[href]')) {
              event.preventDefault();
            }
          }
          setTemplateTabIndex();
        }

        /**
         * Set template tab index.
         *
         * @see https://www.w3.org/TR/wai-aria-practices/#kbd_roving_tabindex
         */
        function setTemplateTabIndex() {
          if (hasMultiple) {
            return;
          }

          // Remove existing tabindex.
          $template
            .find('[data-option-value][tabindex="0"]')
            .attr('tabindex', '-1');

          // Find checked.
          var $checked = $template
            .find('[data-option-value][aria-checked="true"]');
          if ($checked.length) {
            // Add tabindex to checked options.
            $checked.not('text').first().attr('tabindex', '0');
          }
          else {
            // Add tabindex to the first not disabled  and <text>
            // template option.
            $template
              .find('[data-option-value]')
              .not('[aria-disabled="true"], text')
              .first()
              .attr('tabindex', '0');
          }
        }

        /**
         * Set the custom options value.
         *
         * @param {string} value
         *  Custom option value.
         */
        function setValue(value) {
          if (selectOptions[value].disabled) {
            return;
          }

          var $templateOption = $template.find('[data-option-value="' + value + '"]');
          if ($templateOption.attr('aria-checked') === 'true') {
            selectOptions[value].selected = false;
            $template.find('[data-option-value="' + value + '"]')
              .attr('aria-checked', 'false');
          }
          else {
            if (!hasMultiple) {
              clearTemplateOptions();
            }
            selectOptions[value].selected = true;
            $template.find('[data-option-value="' + value + '"]')
              .attr('aria-checked', 'true');
          }

          // Never alter SVG <text> elements.
          if ($templateOption[0].tagName === 'text') {
            $template
              .find('[data-option-value="' + value + '"]')
              .not('text')
              .first()
              .focus();
          }

          $select.change();
        }

        /* ****************************************************************** */
        /*  Initialize methods. */
        /* ****************************************************************** */

        /**
         * Initialize a select option.
         *
         * @param {object} option
         *   The select option.
         */
        function initializeSelectOption(option) {
          // Get description and set text.
          var text = option.text;
          var description = '';
          if (text.indexOf(' -- ') !== -1) {
            var parts = text.split(' -- ');
            text = parts[0];
            description = parts[1];
            // Reset option text.
            option.text = text;
            option.description = description;
          }
        }

        /**
         * Initialize a template option.
         *
         * @param {object} $templateOption
         *   The template option.
         * @param {object} option
         *   The select option.
         */
        function initializeTemplateOption($templateOption, option) {
          // Never alter SVG <text> elements.
          if ($templateOption[0].tagName === 'text') {
            return;
          }

          // Set ARIA attributes.
          $templateOption
            .attr('role', (hasMultiple) ? 'radio' : 'checkbox')
            .attr('aria-checked', 'false');

          // Remove SVG fill style property so that we can change an option's
          // fill property via CSS.
          // @see webform_options_custom.element.css
          if (hasFill) {
            $templateOption.css('fill', '');
          }

          // Set tabindex or disabled.
          if (option.disabled) {
            $templateOption.attr('aria-disabled', 'true');
          }
          else {
            $templateOption.attr('tabindex', (hasMultiple) ? '0' : '-1');
          }
        }

        /**
         * Initialize a template tooltip.
         *
         * @param {object} $templateOption
         *   The template option.
         * @param {object} option
         *   The select option.
         */
        function initializeTemplateTooltip($templateOption, option) {
          if (!hasTooltip) {
            return;
          }

          var content = '<div class="webform-options-custom-tooltip--text" data-tooltip-value="' + Drupal.checkPlain(option.value) + '">' + Drupal.checkPlain(option.text) + '</div>';
          if (option.description) {
            content += '<div class="webform-options-custom-tooltip--description">' + option.description + '</div>';
          }

          if (typeof $.ui.tooltip != 'undefined') {
            // jQuery UI tooltip support.
            var tooltipOptions = $.extend({
              content: content,
              items: '[data-option-value]',
              open: function (event, ui) {
                $(ui.tooltip).on('click', function () {
                  var value = $(this)
                    .find('[data-tooltip-value]')
                    .attr('data-tooltip-value');
                  setValue(value);
                });
              }
            }, Drupal.webformOptionsCustom.jQueryUiTooltip.options);

            $templateOption.tooltip(tooltipOptions);
          }
          else if ((typeof $.fn.tooltip) != 'undefined') {
            // Bootstrap tooltip support.
            var options = $.extend({
              html: true,
              title: content
            }, Drupal.webformOptionsCustom.bootstrapTooltip.options);

            $templateOption
              .tooltip(options)
              .on('show.bs.tooltip', function (event) {
                $templateOptions.not($templateOption).tooltip('hide');
              });
          }
        }

        /**
         * Initialize SVG pan and zoom.
         */
        function initializeZoom() {
          if (!hasZoom || !window.svgPanZoom || !$svg.length) {
            return;
          }
          var options = $.extend({
          }, Drupal.webformOptionsCustom.panAndZoom.options);
          var panZoom = window.svgPanZoom($svg[0], options);
          $(window).resize(function () {
            panZoom.resize();
            panZoom.fit();
            panZoom.center();
          });
        }

        /* ****************************************************************** */
        /*  Clear methods. */
        /* ****************************************************************** */

        /**
         * Clear all template options.
         */
        function clearTemplateOptions() {
          $templateOptions.attr('aria-checked', 'false');
        }

      });
    }
  };

})(jQuery, Drupal);
