<?php

namespace Drupal\webform\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Provides an Ajax command for scrolling to the top of an element.
 *
 * This command is implemented in Drupal.AjaxCommands.prototype.webformScrollTop.
 */
class WebformScrollTopCommand implements CommandInterface {

  /**
   * A CSS selector string.
   *
   * @var string
   */
  protected $selector;

  /**
   * Scroll to target.
   *
   * @var string
   */
  protected $target;

  /**
   * Constructs a \Drupal\webform\Ajax\ScrollTopCommand object.
   *
   * @param string $selector
   *   A CSS selector.
   * @param string $target
   *   Scroll to target which can be 'form' or 'page'. Defaults to 'form'.
   */
  public function __construct($selector, $target = 'form') {
    $this->selector = $selector;
    $this->target = $target;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'webformScrollTop',
      'selector' => $this->selector,
      'target' => $this->target,
    ];
  }

}
