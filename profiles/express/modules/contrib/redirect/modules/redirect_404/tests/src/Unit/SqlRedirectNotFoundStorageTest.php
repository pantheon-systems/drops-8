<?php

namespace Drupal\Tests\redirect_404\Unit;

use Drupal\Core\Database\Connection;
use Drupal\Core\Language\LanguageInterface;
use Drupal\redirect_404\SqlRedirectNotFoundStorage;
use Drupal\Tests\UnitTestCase;

/**
 * Tests that overly long paths aren't logged.
 *
 * @group redirect_404
 */
class SqlRedirectNotFoundStorageTest extends UnitTestCase {

  /**
   * Mock database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->database = $this->getMockBuilder(Connection::class)
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * Tests that long paths aren't stored in the database.
   */
  public function testLongPath() {
    $this->database->expects($this->never())
      ->method('merge');
    $storage = new SqlRedirectNotFoundStorage($this->database, $this->getConfigFactoryStub());
    $storage->logRequest($this->randomMachineName(SqlRedirectNotFoundStorage::MAX_PATH_LENGTH + 1), LanguageInterface::LANGCODE_DEFAULT);
  }

  /**
   * Tests that invalid UTF-8 paths are not stored in the database.
   */
  public function testInvalidUtf8Path() {
    $this->database->expects($this->never())
      ->method('merge');
    $storage = new SqlRedirectNotFoundStorage($this->database, $this->getConfigFactoryStub());
    $storage->logRequest("Caf\xc3", LanguageInterface::LANGCODE_DEFAULT);
  }

}
