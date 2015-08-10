<?php

/**
 * @file
 * Contains \Drupal\text\Tests\Migrate\d6\MigrateTextConfigsTest.
 */

namespace Drupal\text\Tests\Migrate\d6;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to text.settings.yml.
 *
 * @group text
 */
class MigrateTextConfigsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('text');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->loadDumps(['Variable.php']);
    $this->executeMigration('d6_text_settings');
  }

  /**
   * Tests migration of text variables to text.settings.yml.
   */
  public function testTextSettings() {
    $config = $this->config('text.settings');
    $this->assertIdentical(456, $config->get('default_summary_length'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'text.settings', $config->get());
  }

}
