<?php

namespace Drupal\webform\Ajax;

use Drupal\Core\Ajax\RedirectCommand;

/**
 * Provides an Ajax command for refreshing webform page.
 *
 * This command is implemented in Drupal.AjaxCommands.prototype.webformRefresh.
 */
class WebformRefreshCommand extends RedirectCommand {

  /**
   * Implements \Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    return [
      'command' => 'webformRefresh',
      'url' => $this->url,
    ];
  }

}
