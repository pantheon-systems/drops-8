<?php

namespace Drupal\Tests\externalauth\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\externalauth\Authmap;

/**
 * Authmap unit tests.
 *
 * @ingroup externalauth
 *
 * @group externalauth
 *
 * @coversDefaultClass \Drupal\externalauth\Authmap
 */
class AuthmapTest extends UnitTestCase {

  /**
   * The mocked database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $connection;

  /**
   * Mock statement.
   *
   * @var \Drupal\Core\Database\Statement|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $statement;

  /**
   * Mock select interface.
   *
   * @var \Drupal\Core\Database\Query\SelectInterface
   */
  protected $select;

  /**
   * Mock delete class.
   *
   * @var \Drupal\Core\Database\Query\Delete
   */
  protected $delete;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a Mock database connection object.
    $this->connection = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();

    // Create a Mock Statement object.
    $this->statement = $this->getMockBuilder('Drupal\Core\Database\Driver\sqlite\Statement')
      ->disableOriginalConstructor()
      ->getMock();

    // Create a Mock Select object and set expectations.
    $this->select = $this->getMockBuilder('Drupal\Core\Database\Query\Select')
      ->disableOriginalConstructor()
      ->getMock();

    $this->select->expects($this->any())
      ->method('fields')
      ->will($this->returnSelf());
    $this->select->expects($this->any())
      ->method('condition')
      ->will($this->returnSelf());
    $this->select->expects($this->any())
      ->method('range')
      ->will($this->returnSelf());
    $this->select->expects($this->any())
      ->method('orderBy')
      ->will($this->returnSelf());

    $this->select->expects($this->any())
      ->method('execute')
      ->will($this->returnValue($this->statement));

    $this->connection->expects($this->any())
      ->method('select')
      ->will($this->returnValue($this->select));

    // Create a Mock Delete object and set expectations.
    $this->delete = $this->getMockBuilder('Drupal\Core\Database\Query\Delete')
      ->disableOriginalConstructor()
      ->getMock();

    $this->delete->expects($this->any())
      ->method('condition')
      ->will($this->returnSelf());

    $this->delete->expects($this->any())
      ->method('execute')
      ->will($this->returnValue($this->statement));
  }

  /**
   * Test save() method.
   *
   * @covers ::save
   * @covers ::__construct
   */
  public function testSave() {
    $account = $this->getMock('Drupal\user\UserInterface');

    $merge = $this->getMockBuilder('Drupal\Core\Database\Query\Merge')
      ->disableOriginalConstructor()
      ->getMock();

    $merge->expects($this->any())
      ->method('keys')
      ->will($this->returnSelf());

    $merge->expects($this->any())
      ->method('fields')
      ->will($this->returnSelf());

    $merge->expects($this->any())
      ->method('execute')
      ->will($this->returnValue($this->statement));

    $this->connection->expects($this->once())
      ->method('merge')
      ->with($this->equalTo('authmap'))
      ->will($this->returnValue($merge));

    $authmap = new Authmap($this->connection);

    $authmap->save($account, "test_provider", "test_authname");
  }

  /**
   * Test get() method.
   *
   * @covers ::get
   * @covers ::__construct
   */
  public function testGet() {
    $actual_data = (object) [
      'authname' => "test_authname",
    ];
    $this->statement->expects($this->any())
      ->method('fetchObject')
      ->will($this->returnValue($actual_data));

    $authmap = new Authmap($this->connection);
    $result = $authmap->get(2, "test_provider");
    $this->assertEquals($result, "test_authname");
  }

  /**
   * Test getAuthData() method.
   *
   * @covers ::getAuthData
   * @covers ::__construct
   */
  public function testGetAuthData() {
    $actual_data = [
      'authname' => "test_authname",
      'data' => "test_data",
    ];
    $this->statement->expects($this->any())
      ->method('fetchAssoc')
      ->will($this->returnValue($actual_data));

    $authmap = new Authmap($this->connection);
    $result = $authmap->getAuthData(2, "test_provider");
    $this->assertEquals(['authname' => "test_authname", "data" => "test_data"], $result);
  }

  /**
   * Test getAll() method.
   *
   * @covers ::getAll
   * @covers ::__construct
   */
  public function testGetAll() {
    $actual_data = [
      'test_provider' => (object) [
        "authname" => "test_authname",
        "provider" => "test_provider",
      ],
      'test_provider2' => (object) [
        "authname" => "test_authname2",
        "provider" => "test_provider2",
      ],
    ];

    $this->statement->expects($this->any())
      ->method('fetchAllAssoc')
      ->will($this->returnValue($actual_data));

    $authmap = new Authmap($this->connection);
    $result = $authmap->getAll(2);
    $expected_data = [
      "test_provider" => "test_authname",
      "test_provider2" => "test_authname2",
    ];
    $this->assertEquals($expected_data, $result);
  }

  /**
   * Test getUid() method.
   *
   * @covers ::getUid
   * @covers ::__construct
   */
  public function testGetUid() {
    $actual_data = (object) [
      "uid" => 2,
    ];

    $this->statement->expects($this->any())
      ->method('fetchObject')
      ->will($this->returnValue($actual_data));

    $authmap = new Authmap($this->connection);
    $result = $authmap->getUid(2, "test_provider");
    $this->assertEquals(2, $result);
  }

  /**
   * Test delete() method.
   *
   * @covers ::delete
   * @covers ::__construct
   */
  public function testDelete() {
    $this->connection->expects($this->once())
      ->method('delete')
      ->with($this->equalTo('authmap'))
      ->will($this->returnValue($this->delete));

    $authmap = new Authmap($this->connection);
    $authmap->delete(2);
  }

  /**
   * Test deleteProviders() method.
   *
   * @covers ::deleteProvider
   * @covers ::__construct
   */
  public function testDeleteProviders() {
    $this->connection->expects($this->once())
      ->method('delete')
      ->with($this->equalTo('authmap'))
      ->will($this->returnValue($this->delete));

    $authmap = new Authmap($this->connection);
    $authmap->delete("test_provider");
  }

}
