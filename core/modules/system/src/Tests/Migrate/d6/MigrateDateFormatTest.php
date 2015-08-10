<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Migrate\d6\MigrateDateFormatTest.
 */

namespace Drupal\system\Tests\Migrate\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\Core\Database\Database;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade date formats to core.date_format.*.yml.
 *
 * @group system
 */
class MigrateDateFormatTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->loadDumps(['Variable.php']);
    $this->executeMigration('d6_date_formats');
  }

  /**
   * Tests the Drupal 6 date formats to Drupal 8 migration.
   */
  public function testDateFormats() {
    $short_date_format = entity_load('date_format', 'short');
    $this->assertIdentical('\S\H\O\R\T m/d/Y - H:i', $short_date_format->getPattern());

    $medium_date_format = entity_load('date_format', 'medium');
    $this->assertIdentical('\M\E\D\I\U\M D, m/d/Y - H:i', $medium_date_format->getPattern());

    $long_date_format = entity_load('date_format', 'long');
    $this->assertIdentical('\L\O\N\G l, F j, Y - H:i', $long_date_format->getPattern());

    // Test that we can re-import using the EntityDateFormat destination.
    Database::getConnection('default', 'migrate')
      ->update('variable')
      ->fields(array('value' => serialize('\S\H\O\R\T d/m/Y - H:i')))
      ->condition('name', 'date_format_short')
      ->execute();
    db_truncate(entity_load('migration', 'd6_date_formats')->getIdMap()->mapTableName())->execute();
    $migration = entity_load_unchanged('migration', 'd6_date_formats');
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();

    $short_date_format = entity_load('date_format', 'short');
    $this->assertIdentical('\S\H\O\R\T d/m/Y - H:i', $short_date_format->getPattern());

  }

}
