/**
 * @file entity_browser.multi_step_display.js
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Registers behaviours related to selected entities.
   */
  Drupal.behaviors.entityBrowserMultiStepDisplay = {
    attach: function (context) {
      var $entities = $(context).find('.entities-list');
      $entities.sortable({
        stop: Drupal.entityBrowserMultiStepDisplay.entitiesReordered
      });

      // Register add/remove entities event handlers.
      $entities.once('register-add-entities')
        .bind('add-entities', Drupal.entityBrowserMultiStepDisplay.addEntities);

      $entities.once('register-remove-entities')
        .bind('remove-entities', Drupal.entityBrowserMultiStepDisplay.removeEntities);

      // Register event for remove button to use AJAX event.
      var $remove_buttons = $entities.find('.entity-browser-remove-selected-entity');
      $remove_buttons.once('register-click').on('click', function (event) {
        event.preventDefault();

        var button_element = $(event.target);
        var remove_entity_id = button_element.attr('data-remove-entity') + '_' + button_element.attr('data-row-id');

        $entities.trigger('remove-entities', [[remove_entity_id]]);
      });

      // Add a toggle button for the display of selected entities.
      var $toggle = $('.entity-browser-show-selection');

      function setToggleText() {
        if($entities.css('display') == 'none') {
          $toggle.val(Drupal.t('Show selected'));
        } else {
          $toggle.val(Drupal.t('Hide selected'));
        }
      }

      if ($entities.length > 0) {
        $toggle.once('register-click').on('click', function (e) {
          e.preventDefault();
          $entities.slideToggle('fast', setToggleText);
        });

        setToggleText();
      }
    }
  };

  Drupal.entityBrowserMultiStepDisplay = {};

  /**
   * Reacts on sorting of the entities.
   *
   * @param {object} event
   *   Event object.
   * @param {object} ui
   *   Object with detailed information about the sort event.
   */
  Drupal.entityBrowserMultiStepDisplay.entitiesReordered = function (event, ui) {
    var items = $(this).find('.item-container');
    for (var i = 0; i < items.length; i++) {
      $(items[i]).find('.weight').val(i);
    }
  };

  /**
   * Remove entities from selection of multistep display.
   *
   * @param {object} event
   *   Event object.
   * @param {Array} entity_ids
   *   Entity IDs that should be removed from selection.
   */
  Drupal.entityBrowserMultiStepDisplay.removeEntities = function (event, entity_ids) {
    var entities_list = $(this);
    var i;

    for (i = 0; i < entity_ids.length; i++) {
      // Remove dom element, and queue entity for removal in backend.
      var element_selector = '[data-drupal-selector="edit-selected-'.concat(entity_ids[i].replace(/_/g, '-'), '"]');
      entities_list.find(element_selector).remove();

      Drupal.entityBrowserCommandQueue.queueCommand(
        'remove',
        {
          entity_id: entity_ids[i]
        }
      );

      // Remove action buttons, if there are no more entities selected.
      if (entities_list.children().length === 0) {
        entities_list.siblings('.entity-browser-use-selected').remove();
        entities_list.siblings('.entity-browser-show-selection').remove();
      }
    }

    entities_list.siblings('[name=ajax_commands_handler]').trigger('execute-commands', [true]);
  };

  /**
   * Add entities into selection of multistep display.
   *
   * @param {object} event
   *   Event object.
   * @param {Array} entity_ids
   *   Entity ID that should be added to selection.
   */
  Drupal.entityBrowserMultiStepDisplay.addEntities = function (event, entity_ids) {
    var entities_list = $(this);
    var i;

    for (i = 0; i < entity_ids.length; i++) {
      // Add proxy element that will be replaced with returned Ajax Command.
      var proxy_element = $('<div></div>').uniqueId();
      entities_list.append(proxy_element);

      Drupal.entityBrowserCommandQueue.queueCommand(
        'add',
        {
          entity_id: entity_ids[i],
          proxy_id: proxy_element.attr('id')
        }
      );
    }

    entities_list.siblings('[name=ajax_commands_handler]').trigger('execute-commands', [true]);
  };

}(jQuery, Drupal));
