<?php

namespace Drupal\externalauth\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Allow event listeners to alter the authmap data that will get stored.
 */
class ExternalAuthAuthmapAlterEvent extends Event {

  /**
   * The name of the service providing external authentication.
   *
   * @var string
   */
  protected $provider;

  /**
   * The unique, external authentication name.
   *
   * This is provided by the authentication provider.
   *
   * @var string
   */
  protected $authname;

  /**
   * The username to generate when registering this user.
   *
   * @var string
   */
  protected $username;

  /**
   * Optional extra (serialized) data to store with the authname.
   *
   * @var mixed
   */
  protected $data;

  /**
   * Constructs a authmap alter event object.
   *
   * @param string $provider
   *   The name of the service providing external authentication.
   * @param string $authname
   *   The unique, external authentication name provided by authentication
   *   provider.
   * @param string $username
   *   The username to generate when registering this user.
   * @param mixed $data
   *   Optional extra (serialized) data to store with the authname.
   */
  public function __construct($provider, $authname, $username, $data = NULL) {
    $this->provider = $provider;
    $this->authname = $authname;
    $this->username = $username;
    $this->data = $data;
  }

  /**
   * Gets the provider.
   *
   * @return string
   *   The name of the service providing external authentication.
   */
  public function getProvider() {
    return $this->provider;
  }

  /**
   * Gets the authname.
   *
   * @return string
   *   The unique, external authentication name provided by authentication
   *   provider.
   */
  public function getAuthname() {
    return $this->authname;
  }

  /**
   * Sets the authname.
   *
   * @param string $authname
   *   The unique, external authentication name provided by authentication
   *   provider.
   */
  public function setAuthname($authname) {
    $this->authname = $authname;
  }

  /**
   * Gets the username.
   *
   * @return string
   *   The username to generate when registering this user.
   */
  public function getUsername() {
    return $this->username;
  }

  /**
   * Sets the username.
   *
   * @param string $username
   *   The username to generate when registering this user.
   */
  public function setUsername($username) {
    $this->username = $username;
  }

  /**
   * Gets the data.
   *
   * @return mixed
   *   Optional extra (serialized) data to store with the authname.
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Sets the data.
   *
   * @param mixed $data
   *   Optional extra (serialized) data to store with the authname.
   */
  public function setData($data) {
    $this->data = $data;
  }

}
