<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Access\RouteProcessorCsrfTest.
 */

namespace Drupal\Tests\Core\Access;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Access\RouteProcessorCsrf;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\Core\Access\RouteProcessorCsrf
 * @group Access
 */
class RouteProcessorCsrfTest extends UnitTestCase {

  /**
   * The mock CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $csrfToken;

  /**
   * The route processor.
   *
   * @var \Drupal\Core\Access\RouteProcessorCsrf
   */
  protected $processor;

  protected function setUp() {
    $this->csrfToken = $this->getMockBuilder('Drupal\Core\Access\CsrfTokenGenerator')
      ->disableOriginalConstructor()
      ->getMock();

    $this->processor = new RouteProcessorCsrf($this->csrfToken);
  }

  /**
 * Tests the processOutbound() method with no _csrf_token route requirement.
 */
  public function testProcessOutboundNoRequirement() {
    $this->csrfToken->expects($this->never())
      ->method('get');

    $route = new Route('/test-path');
    $parameters = array();

    $cacheable_metadata = new CacheableMetadata();
    $this->processor->processOutbound('test', $route, $parameters, $cacheable_metadata);
    // No parameters should be added to the parameters array.
    $this->assertEmpty($parameters);
    // Cacheability of routes without a _csrf_token route requirement is
    // unaffected.
    $this->assertEquals((new CacheableMetadata()), $cacheable_metadata);
  }

  /**
   * Tests the processOutbound() method with a _csrf_token route requirement.
   */
  public function testProcessOutbound() {
    $this->csrfToken->expects($this->once())
      ->method('get')
      // The leading '/' will be stripped from the path.
      ->with('test-path')
      ->will($this->returnValue('test_token'));

    $route = new Route('/test-path', array(), array('_csrf_token' => 'TRUE'));
    $parameters = array();

    $cacheable_metadata = new CacheableMetadata();
    $this->processor->processOutbound('test', $route, $parameters, $cacheable_metadata);
    // 'token' should be added to the parameters array.
    $this->assertArrayHasKey('token', $parameters);
    $this->assertSame($parameters['token'], 'test_token');
    // Cacheability of routes with a _csrf_token route requirement is max-age=0.
    $this->assertEquals((new CacheableMetadata())->setCacheMaxAge(0), $cacheable_metadata);
  }

  /**
   * Tests the processOutbound() method with a dynamic path and one replacement.
   */
  public function testProcessOutboundDynamicOne() {
    $this->csrfToken->expects($this->once())
      ->method('get')
      ->with('test-path/100')
      ->will($this->returnValue('test_token'));

    $route = new Route('/test-path/{slug}', array(), array('_csrf_token' => 'TRUE'));
    $parameters = array('slug' => 100);

    $cacheable_metadata = new CacheableMetadata();
    $this->processor->processOutbound('test', $route, $parameters, $cacheable_metadata);
    // Cacheability of routes with a _csrf_token route requirement is max-age=0.
    $this->assertEquals((new CacheableMetadata())->setCacheMaxAge(0), $cacheable_metadata);
  }

  /**
   * Tests the processOutbound() method with two parameter replacements.
   */
  public function testProcessOutboundDynamicTwo() {
    $this->csrfToken->expects($this->once())
      ->method('get')
      ->with('100/test-path/test')
      ->will($this->returnValue('test_token'));

    $route = new Route('{slug_1}/test-path/{slug_2}', array(), array('_csrf_token' => 'TRUE'));
    $parameters = array('slug_1' => 100, 'slug_2' => 'test');

    $cacheable_metadata = new CacheableMetadata();
    $this->processor->processOutbound('test', $route, $parameters, $cacheable_metadata);
    // Cacheability of routes with a _csrf_token route requirement is max-age=0.
    $this->assertEquals((new CacheableMetadata())->setCacheMaxAge(0), $cacheable_metadata);
  }

}
