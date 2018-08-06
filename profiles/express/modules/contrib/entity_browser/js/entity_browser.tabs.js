/**
 * @file Entity_browser.admin.js.
 *
 * Defines the behavior of the entity browser's tab display.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Registers behaviours related to tab display.
   */
  Drupal.behaviors.entityBrowserTabs = {
    attach: function (context) {
      var $form = $(context).find('.entity-browser-form').once('entity-browser-admin');
      if (!$form.length) {
        return;
      }

      var $nav = $('<nav class="eb-tabs"></nav>');
      var $tabs = $(Drupal.theme('entityTabs'));

      $form.find('.tab').each(function (index, element) {
        var $element = $(element);
        var classesArray = [];
        classesArray.push($element.attr('disabled') ? 'is-active active' : '');
        var text = $element.text();
        var textClass = text.toLowerCase().replace(/ /g, '-');
        classesArray.push(textClass);
        var classes = classesArray.join(' ');
        var tabSettings = {
          class: classes,
          id: $element.attr('id'),
          title: $(this)[0].value
        };
        var $tab = $(Drupal.theme('entityTab', tabSettings));

        // Add a click event handler that submits the hidden input buttons.
        $tab.find('a').on('click', function (event) {
          var buttonID = $(event.currentTarget).data().buttonId;
          $form.find('#' + buttonID).trigger('click');
          event.preventDefault();
        });
        $tab.appendTo($tabs);
      });
      $tabs.appendTo($nav);
      $nav.prependTo($form);
      $form.find('.tab').css('display', 'none');
    }
  };

  /**
   * Theme function for entity browser tabs.
   *
   * @return {object}
   *   This function returns a jQuery object.
   */
  Drupal.theme.entityTabs = function () {
    return $('<ul role="navigation" aria-label="Tabs"></ul>');
  };

  /**
   * Theme function for an entity browser tab.
   *
   * @param {object} settings
   *   An object with the following keys:
   * @param {string} settings.title
   *   The name of the tab.
   * @param {string} settings.class
   *   Classes for the tab.
   * @param {string} settings.id
   *   ID for the data- button ID.
   *
   * @return {object}
   *   This function returns a jQuery object.
   */
  Drupal.theme.entityTab = function (settings) {
    return $('<li tabindex="-1"></li>')
      .addClass(settings.class)
      .append($('<a href="#"></a>').addClass(settings.class).attr('data-button-id', settings.id)
      .append(settings.title)
    );
  };

}(jQuery, Drupal, drupalSettings));
