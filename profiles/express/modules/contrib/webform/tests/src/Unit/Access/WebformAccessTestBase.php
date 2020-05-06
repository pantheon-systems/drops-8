<?php

namespace Drupal\Tests\webform\Unit\Access;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * Base class for test access checks.
 */
abstract class WebformAccessTestBase extends UnitTestCase {

  /**
   * The test container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->container = new ContainerBuilder();
    \Drupal::setContainer($this->container);

    // Mock cache context manager and set container.
    // @copied from \Drupal\Tests\Core\Access\AccessResultTest::setUp
    $cache_contexts_manager = $this->getMockBuilder('Drupal\Core\Cache\Context\CacheContextsManager')
      ->disableOriginalConstructor()
      ->getMock();

    $cache_contexts_manager->method('assertValidTokens')->willReturn(TRUE);
    $this->container->set('cache_contexts_manager', $cache_contexts_manager);
  }

  /**
   * Create a mock account with permissions.
   *
   * @param array $permissions
   *   An associative array of permissions and results.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   A mock account with ::hasPermission method.
   */
  protected function mockAccount(array $permissions = []) {
    // Convert permission to value map.
    $value_map = [];
    foreach ($permissions as $permission => $result) {
      $value_map[] = [$permission, $result];
    }

    $account = $this->createMock('Drupal\Core\Session\AccountInterface');

    $account->expects($this->any())
      ->method('hasPermission')
      ->will($this->returnValueMap($value_map));

    /** @var \Drupal\Core\Session\AccountInterface $account */
    return $account;
  }

}
