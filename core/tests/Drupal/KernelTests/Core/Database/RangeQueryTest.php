<?php

/**
 * @file
 * Contains \Drupal\KernelTests\Core\Database\RangeQueryTest.
 */

namespace Drupal\KernelTests\Core\Database;

/**
 * Tests the Range query functionality.
 *
 * @group Database
 */
class RangeQueryTest extends DatabaseTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('database_test');

  /**
   * Confirms that range queries work and return the correct result.
   */
  function testRangeQuery() {
    // Test if return correct number of rows.
    $range_rows = db_query_range("SELECT name FROM {test} ORDER BY name", 1, 3)->fetchAll();
    $this->assertEqual(count($range_rows), 3, 'Range query work and return correct number of rows.');

    // Test if return target data.
    $raw_rows = db_query('SELECT name FROM {test} ORDER BY name')->fetchAll();
    $raw_rows = array_slice($raw_rows, 1, 3);
    $this->assertEqual($range_rows, $raw_rows);
  }
}
