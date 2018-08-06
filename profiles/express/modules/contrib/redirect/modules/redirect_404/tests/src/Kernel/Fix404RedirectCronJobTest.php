<?php

namespace Drupal\Tests\redirect_404\Kernel;

use Drupal\Core\Database\Database;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the clean up cron job for redirect_404.
 *
 * @group redirect_404
 */
class Fix404RedirectCronJobTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['redirect_404'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('redirect_404', 'redirect_404');

    // Insert some records in the test table with a given count and timestamp.
    $this->insert404Row('/test1', 12, strtotime('now'));
    $this->insert404Row('/test2', 5, strtotime('-1 hour'));
    $this->insert404Row('/test3', 315, strtotime('-1 week'));
    $this->insert404Row('/test4', 300, strtotime('-1 month'));
    $this->insert404Row('/test5', 1557, strtotime('-1 week'));
    $this->insert404Row('/test6', 1, strtotime('-1 day'));
  }

  /**
   * Tests adding and deleting rows from redirect_404 table.
   */
  function testRedirect404CronJob() {
    // Set the limit to 3 just for the test.
    \Drupal::configFactory()
      ->getEditable('redirect_404.settings')
      ->set('row_limit', 3)
      ->save();

    // Check that there are 6 rows in the redirect_404 table.
    $result = db_query("SELECT COUNT(*) as rows FROM {redirect_404}")->fetchField();
    $this->assertEquals(6, $result);

    // Run cron to drop 3 rows from the redirect_404 test table.
    redirect_404_cron();

    $result = db_query("SELECT COUNT(*) as rows FROM {redirect_404}")->fetchField();
    $this->assertEquals(3, $result);

    // Check there are only 3 rows with more count in the redirect_404 table.
    if (\Drupal::database()->driver() == 'mysql' || \Drupal::database()->driver() == 'pgsql') {
      $this->assertNo404Row('/test1');
      $this->assertNo404Row('/test2');
      $this->assert404Row('/test3');
      $this->assert404Row('/test4');
      $this->assert404Row('/test5');
      $this->assertNo404Row('/test6');
    }
    else {
      // In SQLite is the opposite: the 3 rows kept are the newest ones.
      $this->assert404Row('/test1');
      $this->assert404Row('/test2');
      $this->assertNo404Row('/test3');
      $this->assertNo404Row('/test4');
      $this->assertNo404Row('/test5');
      $this->assert404Row('/test6');
    }
  }

  /**
   * Tests adding rows and deleting one row from redirect_404 table.
   */
  function testRedirect404CronJobKeepAllButOne() {
    // Set the limit to 5 just for the test.
    \Drupal::configFactory()
      ->getEditable('redirect_404.settings')
      ->set('row_limit', 5)
      ->save();

    // Check that there are 6 rows in the redirect_404 table.
    $result = db_query("SELECT COUNT(*) as rows FROM {redirect_404}")->fetchField();
    $this->assertEquals(6, $result);

    // Run cron to drop just 1 row from the redirect_404 test table.
    redirect_404_cron();

    $result = db_query("SELECT COUNT(*) as rows FROM {redirect_404}")->fetchField();
    $this->assertEquals(5, $result);

    // Check only the row with least count has been removed from the table.
    if (\Drupal::database()->driver() == 'mysql' || \Drupal::database()->driver() == 'pgsql') {
      $this->assert404Row('/test1');
      $this->assert404Row('/test2');
      $this->assert404Row('/test3');
      $this->assert404Row('/test4');
      $this->assert404Row('/test5');
      $this->assertNo404Row('/test6');
    }
    else {
      // In SQlite, only the oldest row is deleted.
      $this->assert404Row('/test1');
      $this->assert404Row('/test2');
      $this->assert404Row('/test3');
      $this->assertNo404Row('/test4');
      $this->assert404Row('/test5');
      $this->assert404Row('/test6');
    }
  }

  /**
   * Inserts a 404 request log in the redirect_404 test table.
   *
   * @param string $path
   *   The path of the request.
   * @param int $count
   *   (optional) The visits count of the request.
   * @param int $timestamp
   *   (optional) The timestamp of the last visited request.
   * @param string $langcode
   *   (optional) The langcode of the request.
   */
  protected function insert404Row($path, $count = 1, $timestamp = 0, $langcode = 'en') {
    db_insert('redirect_404')
    ->fields([
      'path' => $path,
      'langcode' => $langcode,
      'count' => $count,
      'timestamp' => $timestamp,
      'resolved' => 0,
    ])
    ->execute();
  }

  /**
   * Passes if the row with the given parameters is in the redirect_404 table.
   *
   * @param string $path
   *   The path of the request.
   * @param string $langcode
   *   (optional) The langcode of the request.
   */
  protected function assert404Row($path, $langcode = 'en') {
    $this->assert404RowHelper($path, $langcode, FALSE);
  }

  /**
   * Passes if the row with the given parameters is NOT in the redirect_404 table.
   *
   * @param string $path
   *   The path of the request.
   * @param string $langcode
   *   (optional) The langcode of the request.
   */
  protected function assertNo404Row($path, $langcode = 'en') {
    $this->assert404RowHelper($path, $langcode, TRUE);
  }

  /**
   * Passes if the row with the given parameters is in the redirect_404 table.
   *
   * @param string $path
   *   The path of the request.
   * @param string $langcode
   *   (optional) The langcode of the request.
   * @param bool $not_exists
   *   (optional) TRUE if this 404 row should not exist in the redirect_404
   *   table, FALSE if it should. Defaults to TRUE.
   */
  protected function assert404RowHelper($path, $langcode = 'en', $not_exists = TRUE) {
    $result = db_select('redirect_404', 'r404')
      ->fields('r404', ['path'])
      ->condition('path', $path)
      ->condition('langcode', $langcode)
      ->condition('resolved', 0)
      ->execute()
      ->fetchField();

    if ($not_exists) {
      $this->assertNotEquals($path, $result);
    }
    else {
      $this->assertEquals($path, $result);
    }
  }
}
