<?php

namespace Drupal\webform\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Provides an Ajax command for confirming page reload.
 *
 * This command is implemented in Drupal.AjaxCommands.prototype.webformConfirmReload.
 */
class WebformConfirmReloadCommand implements CommandInterface {

  /**
   * The message to be displayed.
   *
   * @var string
   */
  protected $message;

  /**
   * Constructs an WebformConfirmReloadCommand object.
   *
   * @param string $message
   *   The message to be displayed.
   */
  public function __construct($message) {
    $this->message = $message;
  }

  /**
   * Implements \Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    return [
      'command' => 'webformConfirmReload',
      'message' => $this->message,
    ];
  }

}
