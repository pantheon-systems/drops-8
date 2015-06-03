<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\RouteProcessor\RouteProcessorManagerTest.
 */

namespace Drupal\Tests\Core\RouteProcessor;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\RouteProcessor\RouteProcessorManager;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\Core\RouteProcessor\RouteProcessorManager
 * @group RouteProcessor
 */
class RouteProcessorManagerTest extends UnitTestCase {

  /**
   * The route processor manager.
   *
   * @var \Drupal\Core\RouteProcessor\RouteProcessorManager
   */
  protected $processorManager;

  protected function setUp() {
    $this->processorManager = new RouteProcessorManager();
  }

  /**
   * Tests the Route process manager functionality.
   */
  public function testRouteProcessorManager() {
    $route = new Route('');
    $parameters = array('test' => 'test');
    $route_name = 'test_name';

    $processors = array(
      10 => $this->getMockProcessor($route_name, $route, $parameters),
      5 => $this->getMockProcessor($route_name, $route, $parameters),
      0 => $this->getMockProcessor($route_name, $route, $parameters),
    );

    // Add the processors in reverse order.
    foreach ($processors as $priority => $processor) {
      $this->processorManager->addOutbound($processor, $priority);
    }

    $cacheable_metadata = new CacheableMetadata();
    $this->processorManager->processOutbound($route_name, $route, $parameters, $cacheable_metadata);
    // Default cacheability is: permanently cacheable, no cache tags/contexts.
    $this->assertEquals((new CacheableMetadata())->setCacheMaxAge(Cache::PERMANENT), $cacheable_metadata);
  }

  /**
   * Returns a mock Route processor object.
   *
   * @param string $route_name
   *   The route name.
   * @param \Symfony\Component\Routing\Route $route
   *   The Route to use in mock with() expectation.
   * @param array $parameters
   *   The parameters to use in mock with() expectation.
   *
   * @return \Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected function getMockProcessor($route_name, $route, $parameters) {
    $processor = $this->getMock('Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface');
    $processor->expects($this->once())
      ->method('processOutbound')
      ->with($route_name, $route, $parameters);

    return $processor;
  }

}
