<?php

namespace Drupal\pathauto;

/**
 * Provides an interface for Messengers.
 */
interface MessengerInterface {

  /**
   * Adds a message.
   *
   * @param string $message
   *   The message to add.
   * @param string $op
   *   (optional) The operation being performed.
   */
  public function addMessage($message, $op = NULL);

}
