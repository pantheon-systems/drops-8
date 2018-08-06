<?php

namespace Drupal\externalauth;

use Drupal\user\UserInterface;

/**
 * Interface AuthmapInterface.
 *
 * @package Drupal\externalauth
 */
interface AuthmapInterface {

  /**
   * Save an external authname for a given Drupal user.
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
  public function save(UserInterface $account, $provider, $authname, $data = NULL);

  /**
   * Get the external authname for a given user ID.
   *
   * @param int $uid
   *   The Drupal user ID.
   * @param string $provider
   *   The name of the service providing external authentication.
   *
   * @return string
   *   The external authname / ID.
   */
  public function get($uid, $provider);

  /**
   * Get the external authname & extra data for a given user ID.
   *
   * @param int $uid
   *   The Drupal user ID.
   * @param string $provider
   *   The name of the service providing external authentication.
   *
   * @return array
   *   An array with authname & data values.
   */
  public function getAuthData($uid, $provider);

  /**
   * Get all external authnames for a given user ID.
   *
   * @param int $uid
   *   The Drupal user ID.
   *
   * @return array
   *   An array of external authnames / IDs for the given user ID, keyed by
   *   provider name.
   */
  public function getAll($uid);

  /**
   * Get a Drupal user ID based on an authname.
   *
   * The authname will be provided by an authentication provider.
   *
   * @param string $authname
   *   The external authname as provided by the authentication provider.
   * @param string $provider
   *   The name of the service providing external authentication.
   *
   * @return int|bool $uid
   *   The Drupal user ID or FALSE.
   */
  public function getUid($authname, $provider);

  /**
   * Delete authmap entries for a given Drupal user ID.
   *
   * @param int $uid
   *   The Drupal user ID.
   */
  public function delete($uid);

  /**
   * Delete all authmap entries for a given provider.
   *
   * @param string $provider
   *   The name of the service providing external authentication.
   */
  public function deleteProvider($provider);

}
