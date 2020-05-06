<?php

namespace Drupal\webform\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Provides an Ajax command to trigger audio UAs to read the supplied text.
 *
 * This command is implemented in Drupal.AjaxCommands.prototype.webformAnnounce.
 */
class WebformAnnounceCommand implements CommandInterface {

  /**
   * A string to be read by the UA.
   *
   * @var string
   */
  protected $text;

  /**
   * A string to indicate the priority of the message.
   *
   * Can be either 'polite' or 'assertive'.
   *
   * @var string
   */
  protected $priority;

  /**
   * Constructs a \Drupal\webform\Ajax\ScrollTopCommand object.
   *
   * @param string $text
   *   A string to be read by the UA.
   * @param string $priority
   *   A string to indicate the priority of the message. Can be either
   *   'polite' or 'assertive'.
   */
  public function __construct($text, $priority = 'polite') {
    $this->text = $text;
    $this->priority = $priority;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'webformAnnounce',
      'text' => $this->text,
      'priority' => $this->priority,
    ];
  }

}
