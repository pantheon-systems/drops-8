<?php

namespace Drupal\Driver;

/**
 * Indicates the driver can log users in and out on the backend.
 */
interface AuthenticationDriverInterface {

  /**
   * Logs the user in.
   */
  public function login(\stdClass $user);

  /**
   * Logs the user out.
   */
  public function logout();

}
