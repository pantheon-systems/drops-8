/**
 * @file entity_browser.common.js
 *
 * Common helper functions used by various parts of entity browser.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.entityBrowser = {};

  /**
   * Command to refresh an entity_browser_entity_reference field widget.
   *
   * @param {Drupal.Ajax} [ajax]
   *   The ajax object.
   * @param {object} response
   *   Object holding the server response.
   * @param {string} response.details_id
   *   The ID for the details element.
   * @param {number} [status]
   *   The HTTP status code.
   */
  Drupal.AjaxCommands.prototype.entity_browser_value_updated = function (ajax, response, status) {
    $('#' + response.details_id)
      .find('input[type="hidden"][name$="[target_id]"]')
      .trigger('entity_browser_value_updated');
  };

  /**
   * Reacts on "entities selected" event.
   *
   * @param {object} event
   *   Event object.
   * @param {string} uuid
   *   Entity browser UUID.
   * @param {array} entities
   *   Array of selected entities.
   */
  Drupal.entityBrowser.selectionCompleted = function (event, uuid, entities) {
    var selected_entities = $.map(entities, function (item) {
      return item[2] + ':' + item[0];
    });
    // @todo Use uuid here. But for this to work we need to move eb uuid
    // generation from display to eb directly. When we do this, we can change
    // \Drupal\entity_browser\Plugin\Field\FieldWidget\EntityReferenceBrowserWidget::formElement
    // also.
    // Checking if cardinality is set - assume unlimited.
    var cardinality = isNaN(parseInt(drupalSettings['entity_browser'][uuid]['cardinality'])) ? -1 : parseInt(drupalSettings['entity_browser'][uuid]['cardinality']);

    // Get field widget selection mode.
    var selection_mode = drupalSettings['entity_browser'][uuid]['selection_mode'];

    // Update value form element with new entity IDs.
    var selector = drupalSettings['entity_browser'][uuid]['selector'] ? $(drupalSettings['entity_browser'][uuid]['selector']) : $(this).parent().parent().find('input[type*=hidden]');
    var entity_ids = selector.val();
    var existing_entities = (entity_ids.length !== 0) ? entity_ids.split(' ') : [];

    entity_ids = Drupal.entityBrowser.updateEntityIds(
      existing_entities,
      selected_entities,
      selection_mode,
      cardinality
    );

    selector.val(entity_ids);
    selector.trigger('entity_browser_value_updated');
  };

  /**
   * Updates the list of selected entities.
   *
   * It uses existing selection and selected entities in entity browser. Also
   * considers cardinality and used selection mode.
   *
   * Note: Selection modes are defined in EntityBrowserElement class and same
   * options should be used here to determine what action will be performed.
   * Default action is append ('selection_append').
   *
   * @param {Array} existing_entities
   *   List of existing entity IDs.
   * @param {Array} selected_entities
   *   The entities that are selected and entity browser.
   * @param {string} selection_mode
   *   Selection mode defined by entity browser field widget.
   * @param {int} cardinality
   *   The maximal amount of items the field can store.
   *
   * @return {string}
   *   List of entities as a string, separated by space.
   */
  Drupal.entityBrowser.updateEntityIds = function (existing_entities, selected_entities, selection_mode, cardinality) {
    var combined_entities;

    if (selection_mode === 'selection_edit') {
      // Propagate new selected entities.
      combined_entities = selected_entities;
    }
    else if (selection_mode === 'selection_prepend') {
      // Prepend selected entities to existing list of entities.
      combined_entities = selected_entities.concat(existing_entities);
    }
    else {
      // Append selected entities to existing list of entities.
      combined_entities = existing_entities.concat(selected_entities);
    }

    // Having more elements than cardinality should never happen, because
    // server side authentication should prevent it, but we handle it here
    // anyway.
    if (cardinality > 0 && combined_entities.length > cardinality) {
      combined_entities = combined_entities.slice(0, cardinality);
    }

    return combined_entities.join(' ');
  };

  /**
   * Reacts on "entities selected" event.
   *
   * @param {object} element
   *   Element to bind on.
   * @param {array} callbacks
   *   List of callbacks.
   * @param {string} event_name
   *   Name of event to bind to.
   */
  Drupal.entityBrowser.registerJsCallbacks = function (element, callbacks, event_name) {
    // JS callbacks are registred as strings. We need to split their names and
    // find actual functions.
    for (var i = 0; i < callbacks.length; i++) {
      var callback = callbacks[i].split('.');
      var fn = window;

      for (var j = 0; j < callback.length; j++) {
        fn = fn[callback[j]];
      }

      if (typeof fn === 'function') {
        $(element).bind(event_name, fn);
      }
    }
  };

}(jQuery, Drupal, drupalSettings));
