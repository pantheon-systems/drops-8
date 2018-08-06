<?php

namespace Drupal\webform\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Provides an AJAX command for scrolling to the top of an element.
 *
 * This command is implemented in Drupal.AjaxCommands.prototype.webformScrollTop.
 */
class ScrollTopCommand implements CommandInterface {

  /**
   * A CSS selector string.
   *
   * @var string
   */
  protected $selector;

  /**
   * Constructs a \Drupal\webform\Ajax\ScrollTopCommand object.
   *
   * @param string $selector
   *   A CSS selector.
   */
  public function __construct($selector) {
    $this->selector = $selector;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'webformScrollTop',
      'selector' => $this->selector,
    ];
  }

}
