<?php

/**
 * @file
 * Contains \Drupal\syslog\Tests\Migrate\d7\MigrateSyslogConfigsTest.
 */

namespace Drupal\syslog\Tests\Migrate\d7;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * Upgrade variables to syslog.settings.yml.
 *
 * @group syslog
 */
class MigrateSyslogConfigsTest extends MigrateDrupal7TestBase {

  use SchemaCheckTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['syslog'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(static::$modules);
    $this->loadDumps(['Variable.php']);
    $this->executeMigration('d7_syslog_settings');
  }

  /**
   * Tests migration of syslog variables to syslog.settings.yml.
   */
  public function testSyslogSettings() {
    $config = $this->config('syslog.settings');
    // 8 == LOG_USER
    $this->assertIdentical('8', $config->get('facility'));
    $this->assertIdentical('!base_url|!timestamp|!type|!ip|!request_uri|!referer|!uid|!link|!message', $config->get('format'));
    $this->assertIdentical('drupal', $config->get('identity'));
  }

}
