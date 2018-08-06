<?php

namespace Drupal\webform\Ajax;

use Drupal\Core\Ajax\HtmlCommand;

/**
 * Provides an Ajax command for calling the jQuery html() method.
 *
 * This command is implemented in Drupal.AjaxCommands.prototype.webformHtml.
 */
class WebformHtmlCommand extends HtmlCommand {

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    return [
      'command' => 'webformInsert',
      'method' => 'html',
      'selector' => $this->selector,
      'data' => $this->getRenderedContent(),
      'settings' => $this->settings,
    ];
  }

}
