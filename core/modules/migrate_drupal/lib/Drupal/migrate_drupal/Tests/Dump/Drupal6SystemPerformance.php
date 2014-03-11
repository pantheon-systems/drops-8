<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6SystemPerformance.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

use Drupal\Core\Database\Connection;

/**
 * Database dump for testing system.performance.yml migration.
 */
class Drupal6SystemPerformance {

  /**
   * Sample database schema and values.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public static function load(Connection $database) {
    Drupal6DumpCommon::createVariable($database);
    $database->insert('variable')->fields(array(
      'name',
      'value',
    ))
    ->values(array(
      'name' => 'preprocess_css',
      'value' => 'i:0;',
    ))
    ->values(array(
      'name' => 'preprocess_js',
      'value' => 'i:0;',
    ))
    ->values(array(
      'name' => 'cache_lifetime',
      'value' => 'i:0;',
    ))
    ->execute();
  }

}
