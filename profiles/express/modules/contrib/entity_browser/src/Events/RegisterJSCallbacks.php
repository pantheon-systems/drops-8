<?php

namespace Drupal\entity_browser\Events;

/**
 * Collects "selection completed" JS callbacks.
 */
class RegisterJSCallbacks extends EventBase {

  /**
   * JS callbacks.
   *
   * @var array
   */
  protected $callbacks = [];

  /**
   * Adds callback.
   *
   * @param string $callback
   *   Callback name.
   */
  public function registerCallback($callback) {
    $this->callbacks[] = $callback;
  }

  /**
   * Remove callback.
   *
   * @param string $callback
   *   Callback name.
   */
  public function removeCallback($callback) {
    $this->callbacks = array_diff($this->callbacks, [$callback]);
  }

  /**
   * Sets callbacks.
   *
   * @param array $callbacks
   *   List of callbacks.
   */
  public function setCallbacks($callbacks) {
    $this->callbacks = $callbacks;
  }

  /**
   * Gets callbacks.
   *
   * @return array
   *   List of callbacks.
   */
  public function getCallbacks() {
    return $this->callbacks;
  }

}
