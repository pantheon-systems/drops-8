<?php

namespace Drupal\externalauth\Event;

use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Notify event listeners about an externalauth user login.
 */
class ExternalAuthLoginEvent extends Event {

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
   * Constructs an external login event object.
   *
   * @param \Drupal\user\UserInterface $account
   *   The Drupal user account.
   * @param string $provider
   *   The name of the service providing external authentication.
   * @param string $authname
   *   The unique, external authentication name provided by authentication
   *   provider.
   */
  public function __construct(UserInterface $account, $provider, $authname) {
    $this->account = $account;
    $this->provider = $provider;
    $this->authname = $authname;
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

}
