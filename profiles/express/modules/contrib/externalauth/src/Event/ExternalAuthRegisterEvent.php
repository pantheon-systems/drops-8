<?php

namespace Drupal\externalauth\Event;

use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Notify event listeners about an externalauth user registration.
 */
class ExternalAuthRegisterEvent extends Event {

  /**
   * The Drupal user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

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
   * Optional extra (serialized) data to store with the authname.
   *
   * @var mixed
   */
  protected $data;

  /**
   * Constructs an external registration event object.
   *
   * @param \Drupal\user\UserInterface $account
   *   The Drupal user account.
   * @param string $provider
   *   The name of the service providing external authentication.
   * @param string $authname
   *   The unique, external authentication name provided by authentication
   *   provider.
   * @param mixed $data
   *   Optional extra (serialized) data to store with the authname.
   */
  public function __construct(UserInterface $account, $provider, $authname, $data = NULL) {
    $this->account = $account;
    $this->provider = $provider;
    $this->authname = $authname;
    $this->data = $data;
  }

  /**
   * Gets the Drupal user entity.
   *
   * @return \Drupal\user\UserInterface $account
   *   The Drupal user account.
   */
  public function getAccount() {
    return $this->account;
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
   * Gets the data.
   *
   * @return mixed
   *   Optional extra (serialized) data to store with the authname.
   */
  public function getData() {
    return $this->data;
  }

}
