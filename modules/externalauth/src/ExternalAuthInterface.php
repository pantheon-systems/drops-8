<?php

namespace Drupal\externalauth;

use Drupal\user\UserInterface;

/**
 * Interface ExternalAuthInterface.
 *
 * @package Drupal\externalauth
 */
interface ExternalAuthInterface {

  /**
   * Load a Drupal user based on an external authname.
   *
   * D7 equivalent: user_external_load().
   *
   * @param string $authname
   *   The unique, external authentication name provided by authentication
   *   provider.
   * @param string $provider
   *   The module providing external authentication.
   *
   * @return \Drupal\user\UserInterface
   *   The loaded Drupal user.
   */
  public function load($authname, $provider);

  /**
   * Log a Drupal user in based on an external authname.
   *
   * @param string $authname
   *   The unique, external authentication name provided by authentication
   *   provider.
   * @param string $provider
   *   The module providing external authentication.
   *
   * @return \Drupal\user\UserInterface|bool
   *   The logged in Drupal user.
   */
  public function login($authname, $provider);

  /**
   * Register a Drupal user based on an external authname.
   *
   * @param string $authname
   *   The unique, external authentication name provided by authentication
   *   provider.
   * @param string $provider
   *   The module providing external authentication.
   * @param array $account_data
   *   An array of additional properties to be saved with the user entity.
   * @param mixed $authmap_data
   *   Additional data to be stored in the authmap entry.
   *
   * @return \Drupal\user\UserInterface
   *   The registered Drupal user.
   */
  public function register($authname, $provider, $account_data = array(), $authmap_data = NULL);

  /**
   * Login and optionally register a Drupal user based on an external authname.
   *
   * @param string $authname
   *   The unique, external authentication name provided by authentication
   *   provider.
   * @param string $provider
   *   The module providing external authentication.
   * @param array $account_data
   *   An array of additional properties to be saved with the user entity.
   * @param mixed $authmap_data
   *   Additional data to be stored in the authmap entry.
   *
   * @return \Drupal\user\UserInterface
   *   The logged in, and optionally registered, Drupal user.
   */
  public function loginRegister($authname, $provider, $account_data = array(), $authmap_data = NULL);

  /**
   * Finalize logging in the external user.
   *
   * Encapsulates user_login_finalize.
   *
   * @param \Drupal\user\UserInterface $account
   *   The Drupal user to finalize login for.
   * @param string $authname
   *   The unique, external authentication name provided by authentication
   *   provider.
   * @param string $provider
   *   The module providing external authentication.
   *
   * @return \Drupal\user\UserInterface
   *   The logged in Drupal user.
   *
   * @codeCoverageIgnore
   */
  public function userLoginFinalize(UserInterface $account, $authname, $provider);

  /**
   * Link a pre-existing Drupal user to a given authname.
   *
   * @param string $authname
   *   The unique, external authentication name provided by authentication
   *   provider.
   * @param string $provider
   *   The module providing external authentication.
   * @param \Drupal\user\UserInterface $account
   *   The existing Drupal account to link.
   */
  public function linkExistingAccount($authname, $provider, UserInterface $account);

}
