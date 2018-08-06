/**
 * @file
 * Linkit Autocomplete based on jQuery UI.
 */

(function ($, Drupal, _, document) {

  'use strict';

  var autocomplete;

  /**
   * JQuery UI autocomplete source callback.
   *
   * @param {object} request
   * @param {function} response
   */
  function sourceData(request, response) {
    var elementId = this.element.attr('id');

    if (!(elementId in autocomplete.cache)) {
      autocomplete.cache[elementId] = {};
    }

    /**
     * @param {object} suggestions
     */
    function showSuggestions(suggestions) {
      response(suggestions.matches);
    }

    /**
     * Transforms the data object into an array and update autocomplete results.
     *
     * @param {object} data
     */
    function sourceCallbackHandler(data) {
      autocomplete.cache[elementId][term] = data;
      showSuggestions(data);
    }

    // Get the desired term and construct the autocomplete URL for it.
    var term = request.term;

    // Check if the term is already cached.
    if (autocomplete.cache[elementId].hasOwnProperty(term)) {
      showSuggestions(autocomplete.cache[elementId][term]);
    }
    else {
      var options = $.extend({success: sourceCallbackHandler, data: {q: term}}, autocomplete.ajax);
      $.ajax(this.element.attr('data-autocomplete-path'), options);
    }
  }

  /**
    * Handles an autocomplete select event.
    *
    * @param {jQuery.Event} event
    * @param {object} ui
    *
    * @return {boolean}
    */
  function selectHandler(event, ui) {
    if (ui.item.hasOwnProperty('path')) {
      event.target.value = ui.item.path;
    }
    $(document).trigger('linkit.autocomplete.select', [event, ui]);
    return false;
  }

  /**
   * Override jQuery UI _renderItem function to output HTML by default.
   *
   * @param {object} ul
   *   The <ul> element that the newly created <li> element must be appended to.
   * @param {object} item
   *
   * @return {object}
   */
  function renderItem(ul, item) {
    var $line = $('<li>').addClass('linkit-result');
    $line.append($('<span>').html(item.title).addClass('linkit-result--title'));

    if (item.description !== null) {
      $line.append($('<span>').html(item.description).addClass('linkit-result--description'));
    }

    return $line.appendTo(ul);
  }

  /**
   * Override jQuery UI _renderMenu function to handle groups.
   *
   * @param {object} ul
   *   An empty <ul> element to use as the widget's menu.
   * @param {array} items
   *   An Array of items that match the user typed term.
   */
  function renderMenu(ul, items) {
    var self = this.element.autocomplete('instance');

    var grouped_items = _.groupBy(items, function (item) {
      return item.hasOwnProperty('group') ? item.group : '';
    });

    $.each(grouped_items, function (group, items) {
      if (group.length) {
        ul.append('<li class="linkit-result--group">' + group + '</li>');
      }

      $.each(items, function (index, item) {
        self._renderItemData(ul, item);
      });
    });
  }

  /**
   * Attaches the autocomplete behavior to all required fields.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.linkit_autocomplete = {
    attach: function (context) {
      // Act on textfields with the "form-autocomplete" class.
      var $autocomplete = $(context).find('input.form-linkit-autocomplete').once('linkit-autocomplete');
      if ($autocomplete.length) {
        $.widget('custom.autocomplete', $.ui.autocomplete, {
          _create: function () {
            this._super();
            this.widget().menu('option', 'items', '> :not(.linkit-result--group)');
          },
          _renderMenu: autocomplete.options.renderMenu,
          _renderItem: autocomplete.options.renderItem
        });

        // Use jQuery UI Autocomplete on the textfield.
        $autocomplete.autocomplete(autocomplete.options);
        $autocomplete.autocomplete('widget').addClass('linkit-ui-autocomplete');
      }
    },
    detach: function (context, settings, trigger) {
      if (trigger === 'unload') {
        $(context).find('input.form-linkit-autocomplete')
          .removeOnce('linkit-autocomplete')
          .autocomplete('destroy');
      }
    }
  };

  /**
   * Autocomplete object implementation.
   */
  autocomplete = {
    cache: {},
    options: {
      source: sourceData,
      renderItem: renderItem,
      renderMenu: renderMenu,
      select: selectHandler,
      minLength: 1
    },
    ajax: {
      dataType: 'json'
    }
  };

})(jQuery, Drupal, _, document);
