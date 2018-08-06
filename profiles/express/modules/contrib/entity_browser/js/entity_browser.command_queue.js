/**
 * @file entity_browser.command_queue.js
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Namespace for command queue functionality.
   *
   * Command queue provides functionality to queue ajax commands in front-end
   * and execute them in one ajax request in backend. All commands triggered
   * during ajax request execution, will be queue and executed after response
   * from previous ajax request is received.
   *
   * @type {Object}
   */
  Drupal.entityBrowserCommandQueue = {};

  /**
   * Queue container for keeping commands for next queue execution. (protected)
   *
   * @type {Object}
   */
  var commandsQueue = {};

  /**
   * Registers behaviours related to Ajax commands execution.
   */
  Drupal.behaviors.entityBrowserCommandQueue = {
    attach: function (context) {
      var handler = $(context).find('[name="ajax_commands_handler"]');

      handler.once('register-execute-commands')
        .bind('execute-commands', Drupal.entityBrowserCommandQueue.executeCommands);
    }
  };

  /**
   * Action to queue command for future execution.
   *
   * @param {string} commandName
   *   Command name, that will be executed.
   * @param {Array} commandParam
   *   Params for command. If command already exists in queue params will be
   *   added to end of list.
   */
  Drupal.entityBrowserCommandQueue.queueCommand = function (commandName, commandParam) {
    if (!commandsQueue[commandName]) {
      commandsQueue[commandName] = [];
    }

    commandsQueue[commandName].push(commandParam);
  };

  /**
   * Handler for executing queued commands over Ajax.
   *
   * @param {object} event
   *   Event object.
   * @param {boolean} addedCommand
   *   Execution of queued commands is triggered after new command is added.
   */
  Drupal.entityBrowserCommandQueue.executeCommands = function (event, addedCommand) {
    var handler = $(this);
    var handlerElement = handler[0];
    var runningAjax = Drupal.entityBrowserCommandQueue.isAjaxRunning(handlerElement, 'execute_js_commands');
    var filledQueue = !$.isEmptyObject(commandsQueue);

    if (!runningAjax && filledQueue) {
      handler.val(JSON.stringify(commandsQueue));

      // Clear Queue after command is set to handler element.
      commandsQueue = {};

      // Trigger event to execute event with defined command.
      handler.trigger('execute_js_commands');
    }
    else if (!addedCommand && filledQueue) {
      setTimeout($.proxy(Drupal.entityBrowserCommandQueue.executeCommands, handlerElement), 200);
    }
  };

  /**
   * Search is there current Ajax request executing for current event.
   *
   * @param {element} handlerElement
   *   Element on what event is triggered.
   * @param {string} eventName
   *   Event name.
   *
   * @return {boolean}
   *   Returns true if ajax event is still running for element.
   */
  Drupal.entityBrowserCommandQueue.isAjaxRunning = function (handlerElement, eventName) {
    var ajax_list = Drupal.ajax.instances;

    for (var i = 0; i < ajax_list.length; i++) {
      if (ajax_list[i] && ajax_list[i].event === eventName && ajax_list[i].element === handlerElement && ajax_list[i].ajaxing) {
        return true;
      }
    }

    return false;
  };

}(jQuery, Drupal));
