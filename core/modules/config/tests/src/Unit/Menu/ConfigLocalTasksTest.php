<?php

/**
 * @file
 * Contains \Drupal\Tests\config\Unit\Menu\ConfigLocalTasksTest.
 */

namespace Drupal\Tests\config\Unit\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTestBase;

/**
 * Tests existence of config local tasks.
 *
 * @group config
 */
class ConfigLocalTasksTest extends LocalTaskIntegrationTestBase {

  protected function setUp() {
    $this->directoryList = array('config' => 'core/modules/config');
    parent::setUp();
  }

  /**
   * Tests config local tasks existence.
   *
   * @dataProvider getConfigAdminRoutes
   */
  public function testConfigAdminLocalTasks($route, $expected) {
    $this->assertLocalTasks($route, $expected);
  }

  /**
   * Provides a list of routes to test.
   */
  public function getConfigAdminRoutes() {
    return array(
      array('config.sync', array(array('config.sync', 'config.full', 'config.single'))),
      array('config.export_full', array(array('config.sync', 'config.full', 'config.single'), array('config.export_full', 'config.import_full'))),
      array('config.import_full', array(array('config.sync', 'config.full', 'config.single'), array('config.export_full', 'config.import_full'))),
      array('config.export_single', array(array('config.sync', 'config.full', 'config.single'), array('config.export_single', 'config.import_single'))),
      array('config.import_single', array(array('config.sync', 'config.full', 'config.single'), array('config.export_single', 'config.import_single'))),
    );
  }

}
