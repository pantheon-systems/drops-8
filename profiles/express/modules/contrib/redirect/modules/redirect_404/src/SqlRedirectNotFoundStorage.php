<?php

namespace Drupal\redirect_404;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;

/**
 * Provides an SQL implementation for redirect not found storage.
 *
 * To keep a limited amount of relevant records, we compute a relevancy based
 * on the amount of visits for each row, deleting the less visited record and
 * sorted by timestamp.
 */
class SqlRedirectNotFoundStorage implements RedirectNotFoundStorageInterface {

  /**
   * Maximum column length for invalid paths.
   */
  const MAX_PATH_LENGTH = 191;

  /**
   * Active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new SqlRedirectNotFoundStorage.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A Database connection to use for reading and writing database data.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(Connection $database, ConfigFactoryInterface $config_factory) {
    $this->database = $database;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function logRequest($path, $langcode) {
    if (Unicode::strlen($path) > static::MAX_PATH_LENGTH) {
      // Don't attempt to log paths that would result in an exception. There is
      // no point in logging truncated paths, as they cannot be used to build a
      // new redirect.
      return;
    }
    // Ignore invalid UTF-8, which can't be logged.
    if (!Unicode::validateUtf8($path)) {
      return;
    }

    // If the request is not new, update its count and timestamp.
    $this->database->merge('redirect_404')
      ->key('path', $path)
      ->key('langcode', $langcode)
      ->expression('count', 'count + 1')
      ->fields([
        'timestamp' => REQUEST_TIME,
        'count' => 1,
        'resolved' => 0,
      ])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function resolveLogRequest($path, $langcode) {
    $this->database->update('redirect_404')
      ->fields(['resolved' => 1])
      ->condition('path', $path)
      ->condition('langcode', $langcode)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function purgeOldRequests() {
    $row_limit = $this->configFactory->get('redirect_404.settings')->get('row_limit');

    $query = $this->database->select('redirect_404', 'r404');
    $query->fields('r404', ['timestamp']);
    // On databases known to support log(), use it to calculate a logarithmic
    // scale of the count, to delete records with count of 1-9 first, then
    // 10-99 and so on.
    if ($this->database->driver() == 'mysql' || $this->database->driver() == 'pgsql') {
      $query->addExpression('floor(log(10, count))', 'count_log');
      $query->orderBy('count_log', 'DESC');
    }
    $query->orderBy('timestamp', 'DESC');
    $cutoff = $query
      ->range($row_limit, 1)
      ->execute()
      ->fetchAssoc();

    if (!empty($cutoff)) {
      // Delete records having older timestamp and less visits (on a logarithmic
      // scale) than cutoff.
      $delete_query = $this->database->delete('redirect_404');

      if ($this->database->driver() == 'mysql' || $this->database->driver() == 'pgsql') {
        // Delete rows with same count_log AND older timestamp than cutoff.
        $and_condition = $delete_query->andConditionGroup()
          ->where('floor(log(10, count)) = :count_log2', [':count_log2' => $cutoff['count_log']])
          ->condition('timestamp', $cutoff['timestamp'], '<=');

        // And delete all the rows with count_log less than the cutoff.
        $condition = $delete_query->orConditionGroup()
          ->where('floor(log(10, count)) < :count_log1', [':count_log1' => $cutoff['count_log']])
          ->condition($and_condition);
        $delete_query->condition($condition);
      }
      else {
        $delete_query->condition('timestamp', $cutoff['timestamp'], '<=');
      }
      $delete_query->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function listRequests(array $header = [], $search = NULL) {
    $query = $this->database
      ->select('redirect_404', 'r404')
      ->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->orderByHeader($header)
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->limit(25)
      ->fields('r404');

    if ($search) {
      // Replace wildcards with PDO wildcards.
      // @todo Find a way to write a nicer pattern.
      $wildcard = '%' . trim(preg_replace('!\*+!', '%', $this->database->escapeLike($search)), '%') . '%';
      $query->condition('path', $wildcard, 'LIKE');
    }
    $results = $query->condition('resolved', 0, '=')->execute()->fetchAll();

    return $results;
  }

}
