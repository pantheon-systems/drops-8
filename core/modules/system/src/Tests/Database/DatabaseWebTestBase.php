<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Database\DatabaseWebTestBase.
 */

namespace Drupal\system\Tests\Database;

use Drupal\KernelTests\Core\Database\DatabaseTestBase;
use Drupal\simpletest\WebTestBase;

/**
 * Base class for databases database tests.
 */
abstract class DatabaseWebTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('database_test');

  protected function setUp() {
    parent::setUp();

    DatabaseTestBase::addSampleData();
  }
}
