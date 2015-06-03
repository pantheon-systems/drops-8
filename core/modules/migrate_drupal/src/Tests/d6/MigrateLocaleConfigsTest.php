<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateLocaleConfigsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to locale.settings.yml.
 *
 * @group migrate_drupal
 */
class MigrateLocaleConfigsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('locale', 'language');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_locale_settings');
    $dumps = array(
      $this->getDumpDirectory() . '/Variable.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests migration of locale variables to locale.settings.yml.
   */
  public function testLocaleSettings() {
    $config = $this->config('locale.settings');
    $this->assertIdentical(TRUE, $config->get('cache_strings'));
    $this->assertIdentical('languages', $config->get('javascript.directory'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'locale.settings', $config->get());
  }

}
