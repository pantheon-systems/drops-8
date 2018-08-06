<?php

namespace Drupal\externalauth;

use Drupal\Core\Database\Connection;
use Drupal\user\UserInterface;

/**
 * Class Authmap.
 *
 * @package Drupal\externalauth
 */
class Authmap implements AuthmapInterface {

  /**
   * The connection object used for this data.
   *
   * @var \Drupal\Core\Database\Connection $connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   *
   * @param Connection $connection
   *   The connection object used for this data.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function save(UserInterface $account, $provider, $authname, $data = NULL) {
    if (!is_scalar($data)) {
      $data = serialize($data);
    }

    // If a mapping (for the same provider) from this authname to a different
    // account already exists, this throws an exception. If a mapping (for the
    // same provider) to this account already exists, the currently stored
    // authname is overwritten.
    $this->connection->merge('authmap')
      ->keys(array(
        'uid' => $account->id(),
        'provider' => $provider,
      ))
      ->fields(array(
        'authname' => $authname,
        'data' => $data,
      ))
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function get($uid, $provider) {
    $authname = $this->connection->select('authmap', 'am')
      ->fields('am', array('authname'))
      ->condition('uid', $uid)
      ->condition('provider', $provider)
      ->range(0, 1)
      ->execute()
      ->fetchObject();
    if ($authname) {
      return $authname->authname;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthData($uid, $provider) {
    $data = $this->connection->select('authmap', 'am')
      ->fields('am', array('authname', 'data'))
      ->condition('uid', $uid)
      ->condition('provider', $provider)
      ->range(0, 1)
      ->execute()
      ->fetchAssoc();
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getAll($uid) {
    $query = $this->connection->select('authmap', 'am')
      ->fields('am', array('provider', 'authname'))
      ->condition('uid', $uid)
      ->orderBy('provider', 'ASC')
      ->execute();
    $result = $query->fetchAllAssoc('provider');
    if ($result) {
      foreach ($result as $provider => $data) {
        $result[$provider] = $data->authname;
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getUid($authname, $provider) {
    $authname = $this->connection->select('authmap', 'am')
      ->fields('am', array('uid'))
      ->condition('authname', $authname)
      ->condition('provider', $provider)
      ->range(0, 1)
      ->execute()
      ->fetchObject();
    if ($authname) {
      return $authname->uid;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($uid) {
    $this->connection->delete('authmap')
      ->condition('uid', $uid)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteProvider($provider) {
    $this->connection->delete('authmap')
      ->condition('provider', $provider)
      ->execute();
  }

}
