<?php

namespace Drupal\Driver\Cores;

/**
 * The core has the ability to directly authenticate users.
 */
interface CoreAuthenticationInterface {

  /**
   * Logs a user in.
   */
  public function login(\stdClass $user);

  /**
   * Logs a user out.
   */
  public function logout();

}
