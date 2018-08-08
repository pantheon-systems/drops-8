<?php

namespace Drupal\Tests\externalauth\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use Drupal\externalauth\ExternalAuth;

/**
 * ExternalAuth unit tests.
 *
 * @ingroup externalauth
 *
 * @group externalauth
 *
 * @coversDefaultClass \Drupal\externalauth\ExternalAuth
 */
class ExternalAuthTest extends UnitTestCase {

  /**
   * The mocked Entity Manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The mocked authmap service.
   *
   * @var \Drupal\externalauth\AuthmapInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $authmap;

  /**
   * The mocked logger instance.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $logger;

  /**
   * The mocked event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a Mock EntityManager object.
    $this->entityManager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');

    // Create a Mock Logger object.
    $this->logger = $this->getMockBuilder('\Psr\Log\LoggerInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Create a Mock EventDispatcher object.
    $this->eventDispatcher = $this->getMockBuilder('\Symfony\Component\EventDispatcher\EventDispatcherInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Create a Mock Authmap object.
    $this->authmap = $this->getMockBuilder('\Drupal\externalauth\Authmap')
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * Test the load() method.
   *
   * @covers ::load
   * @covers ::__construct
   */
  public function testLoad() {
    // Set up a mock for Authmap class,
    // mocking getUid() method.
    $authmap = $this->getMockBuilder('\Drupal\externalauth\Authmap')
      ->disableOriginalConstructor()
      ->setMethods(array('getUid'))
      ->getMock();

    $authmap->expects($this->once())
      ->method('getUid')
      ->will($this->returnValue(2));

    // Mock the User storage layer.
    $account = $this->getMock('Drupal\user\UserInterface');
    $entity_storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    // Expect the external loading method to return a user object.
    $entity_storage->expects($this->once())
      ->method('load')
      ->will($this->returnValue($account));
    $this->entityManager->expects($this->once())
      ->method('getStorage')
      ->will($this->returnValue($entity_storage));

    $externalauth = new ExternalAuth(
      $this->entityManager,
      $authmap,
      $this->logger,
      $this->eventDispatcher
    );
    $result = $externalauth->load("test_authname", "test_provider");
    $this->assertTrue($result instanceof UserInterface);
  }

  /**
   * Test the login() method.
   *
   * @covers ::login
   * @covers ::__construct
   */
  public function testLogin() {
    // Set up a mock for ExternalAuth class,
    // mocking load() & userLoginFinalize() methods.
    $externalauth = $this->getMockBuilder('Drupal\externalauth\ExternalAuth')
      ->setMethods(array('load', 'userLoginFinalize'))
      ->setConstructorArgs(array(
        $this->entityManager,
        $this->authmap,
        $this->logger,
        $this->eventDispatcher,
      ))
      ->getMock();

    // Mock load method.
    $externalauth->expects($this->once())
      ->method('load')
      ->will($this->returnValue(FALSE));

    // Expect userLoginFinalize() to not be called.
    $externalauth->expects($this->never())
      ->method('userLoginFinalize');

    $result = $externalauth->login("test_authname", "test_provider");
    $this->assertEquals(FALSE, $result);
  }

  /**
   * Test the register() method.
   *
   * @covers ::register
   * @covers ::__construct
   *
   * @dataProvider registerDataProvider
   */
  public function testRegister($registration_data, $expected_data) {
    // Mock the returned User object.
    $account = $this->getMock('Drupal\user\UserInterface');
    $account->expects($this->once())
      ->method('enforceIsNew');
    $account->expects($this->once())
      ->method('save');
    $account->expects($this->any())
      ->method('getTimeZone')
      ->will($this->returnValue($expected_data['timezone']));

    // Mock the User storage layer to create a new user.
    $entity_storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    // Expect the external registration to return us a user object.
    $entity_storage->expects($this->any())
      ->method('create')
      ->will($this->returnValue($account));
    $entity_storage->expects($this->any())
      ->method('loadByProperties')
      ->will($this->returnValue(array()));
    $this->entityManager->expects($this->any())
      ->method('getStorage')
      ->will($this->returnValue($entity_storage));

    // Set up a mock for Authmap class,
    // mocking getUid() method.
    $authmap = $this->getMockBuilder('\Drupal\externalauth\Authmap')
      ->disableOriginalConstructor()
      ->setMethods(array('save'))
      ->getMock();

    $authmap->expects($this->once())
      ->method('save');

    $dispatched_event = $this->getMockBuilder('\Drupal\externalauth\Event\ExternalAuthAuthmapAlterEvent')
      ->disableOriginalConstructor()
      ->getMock();

    $dispatched_event->expects($this->any())
      ->method('getUsername')
      ->will($this->returnValue($expected_data['username']));
    $dispatched_event->expects($this->any())
      ->method('getAuthname')
      ->will($this->returnValue($expected_data['authname']));
    $dispatched_event->expects($this->any())
      ->method('getData')
      ->will($this->returnValue($expected_data['data']));

    $this->eventDispatcher->expects($this->any())
      ->method('dispatch')
      ->will($this->returnValue($dispatched_event));

    $externalauth = new ExternalAuth(
      $this->entityManager,
      $authmap,
      $this->logger,
      $this->eventDispatcher
    );
    $registered_account = $externalauth->register($registration_data['authname'], $registration_data['provider'], $registration_data['account_data'], $registration_data['authmap_data']);
    $this->assertTrue($registered_account instanceof UserInterface);
    $this->assertEquals($expected_data['timezone'], $registered_account->getTimeZone());
    $this->assertEquals($expected_data['data'], $dispatched_event->getData());
  }

  /**
   * Provides test data for testRegister.
   *
   * @return array
   *   Parameters
   */
  public function registerDataProvider() {
    return [
      // Test basic registration.
      [
        [
          'authname' => 'test_authname',
          'provider' => 'test_provider',
          'account_data' => [],
          'authmap_data' => NULL,
        ],
        [
          'username' => 'test_provider-test_authname',
          'authname' => 'test_authname',
          'timezone' => 'Europe/Brussels',
          'data' => [],
        ],
      ],
      // Test with added account data.
      [
        [
          'authname' => 'test_authname',
          'provider' => 'test_provider',
          'account_data' => ['timezone' => 'Europe/Prague'],
          'authmap_data' => NULL,
        ],
        [
          'username' => 'test_provider-test_authname',
          'authname' => 'test_authname',
          'timezone' => 'Europe/Prague',
          'data' => [],
        ],
      ],
      // Test with added authmap data.
      [
        [
          'authname' => 'test_authname',
          'provider' => 'test_provider',
          'account_data' => [],
          'authmap_data' => ['extra_property' => 'extra'],
        ],
        [
          'username' => 'test_provider-test_authname',
          'authname' => 'test_authname',
          'timezone' => 'Europe/Brussels',
          'data' => ['extra_property' => 'extra'],
        ],
      ],
    ];
  }

  /**
   * Test the loginRegister() method.
   *
   * @covers ::loginRegister
   * @covers ::__construct
   */
  public function testLoginRegister() {
    $account = $this->getMock('Drupal\user\UserInterface');

    // Set up a mock for ExternalAuth class,
    // mocking login(), register() & userLoginFinalize() methods.
    $externalauth = $this->getMockBuilder('Drupal\externalauth\ExternalAuth')
      ->setMethods(array('login', 'register', 'userLoginFinalize'))
      ->setConstructorArgs(array(
        $this->entityManager,
        $this->authmap,
        $this->logger,
        $this->eventDispatcher,
      ))
      ->getMock();

    // Mock ExternalAuth methods.
    $externalauth->expects($this->once())
      ->method('login')
      ->will($this->returnValue(FALSE));
    $externalauth->expects($this->once())
      ->method('register')
      ->will($this->returnValue($account));
    $externalauth->expects($this->once())
      ->method('userLoginFinalize')
      ->will($this->returnValue($account));

    $result = $externalauth->loginRegister("test_authname", "test_provider");
    $this->assertTrue($result instanceof UserInterface);
  }

  /**
   * Test linking an existing account.
   */
  public function testLinkExistingAccount() {
    $account = $this->getMock('Drupal\user\UserInterface');
    $account->expects($this->once())
      ->method('id')
      ->will($this->returnValue(5));

    // Set up a mock for Authmap class,
    // mocking get() & save() methods.
    $authmap = $this->getMockBuilder('\Drupal\externalauth\Authmap')
      ->disableOriginalConstructor()
      ->setMethods(array('save', 'get'))
      ->getMock();

    $authmap->expects($this->once())
      ->method('get')
      ->will($this->returnValue(FALSE));

    $authmap->expects($this->once())
      ->method('save');

    $dispatched_event = $this->getMockBuilder('\Drupal\externalauth\Event\ExternalAuthAuthmapAlterEvent')
      ->disableOriginalConstructor()
      ->getMock();

    $dispatched_event->expects($this->any())
      ->method('getUsername')
      ->will($this->returnValue("Test username"));
    $dispatched_event->expects($this->any())
      ->method('getAuthname')
      ->will($this->returnValue("Test authname"));
    $dispatched_event->expects($this->any())
      ->method('getData')
      ->will($this->returnValue("Test data"));

    $this->eventDispatcher->expects($this->any())
      ->method('dispatch')
      ->will($this->returnValue($dispatched_event));

    $externalauth = new ExternalAuth(
      $this->entityManager,
      $authmap,
      $this->logger,
      $this->eventDispatcher
    );
    $externalauth->linkExistingAccount("test_authname", "test_provider", $account);
  }

}
