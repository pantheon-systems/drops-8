<?php

/**
 * @file
 * Contains \Drupal\action\Tests\Migrate\d6\MigrateActionConfigsTest.
 */

namespace Drupal\action\Tests\Migrate\d6;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to action.settings.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateActionConfigsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['action'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d6_action_settings');
  }

  /**
   * Tests migration of action variables to action.settings.yml.
   */
  public function testActionSettings() {
    $config = $this->config('action.settings');
    $this->assertIdentical(35, $config->get('recursion_limit'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'action.settings', $config->get());
  }

}
