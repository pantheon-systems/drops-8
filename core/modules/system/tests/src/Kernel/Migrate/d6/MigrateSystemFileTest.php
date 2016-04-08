<?php

/**
 * @file
 * Contains \Drupal\Tests\system\Kernel\Migrate\d6\MigrateSystemFileTest.
 */

namespace Drupal\Tests\system\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to system.*.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateSystemFileTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d6_system_file');
  }

  /**
   * Tests migration of system (file) variables to system.file.yml.
   */
  public function testSystemFile() {
    $config = \Drupal::configFactory()->getEditable('system.file');
    $this->assertIdentical('files/temp', $config->get('path.temporary'));
    $this->assertIdentical(TRUE, $config->get('allow_insecure_uploads'));
  }

}
