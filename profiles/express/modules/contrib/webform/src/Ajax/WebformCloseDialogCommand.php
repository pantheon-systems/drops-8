<?php

namespace Drupal\webform\Ajax;

use Drupal\Core\Ajax\CloseDialogCommand;

/**
 * Provides an Ajax command for closing webform dialog and system tray.
 *
 * This command is implemented in Drupal.AjaxCommands.prototype.webformCloseDialog.
 */
class WebformCloseDialogCommand extends CloseDialogCommand {

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'webformCloseDialog',
      'selector' => $this->selector,
      'persist' => $this->persist,
    ];
  }

}
