<?php

namespace Drupal\Tests\redirect_domain\Unit;

use Drupal\Core\Path\PathMatcher;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\redirect\RedirectChecker;
use Drupal\redirect_domain\EventSubscriber\DomainRedirectRequestSubscriber;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the redirect logic.
 *
 * @group redirect_domain
 *
 * @coversDefaultClass Drupal\redirect_domain\EventSubscriber\DomainRedirectRequestSubscriber
 */
class DomainRedirectRequestSubscriberTest extends UnitTestCase {

  /**
   * Tests redirect between domains.
   *
   * @dataProvider providerDomains
   */
  public function testDomainRedirect($request_url, $response_url) {
    $data = [
      'redirect_domain.domains' => [
        'domain_redirects' => [
          'foo:com' => [
            [
              'sub_path' => '/fixedredirect',
              'destination' => 'bar.com/fixedredirect',
            ],
            [
              'sub_path' => '/*',
              'destination' => 'bar.com/example',
            ],
          ],
          'example:com' => [
            [
              'sub_path' => '/foo/*/bar',
              'destination' => 'example.com/bar/foo',
            ],
          ],
          'simpleexample:com' => [
            [
              'sub_path' => '/redirect',
              'destination' => 'redirected.com/redirect',
            ],
          ],
        ],
      ],
      'redirect.settings' => [
        'default_status_code' => 301,
      ],
      'system.site' => [
        'page.front' => '/',
      ],
    ];

    // Create a mock redirect checker.
    $checker = $this->getMockBuilder(RedirectChecker::class)
      ->disableOriginalConstructor()
      ->getMock();
    $checker->expects($this->any())
      ->method('canRedirect')
      ->will($this->returnValue(TRUE));

    // Set up the configuration for the requested domain.
    $config_factory = $this->getConfigFactoryStub($data);

    // Create a mock path matcher.
    $route_match = $this->getMock(RouteMatchInterface::class);
    $path_matcher = new PathMatcher($config_factory, $route_match);

    $subscriber = new DomainRedirectRequestSubscriber(
      $config_factory,
      $checker,
      $path_matcher
    );

    // Make a request to the urls from the data provider and get the response.
    $event = $this->getGetResponseEventStub($request_url, http_build_query([]));

    // Run the main redirect method.
    $subscriber->onKernelRequestCheckDomainRedirect($event);

    // Assert the expected response from the data provider.
    if ($response_url) {
      $this->assertTrue($event->getResponse() instanceof RedirectResponse);
      $response = $event->getResponse();
      // Make sure that the response is properly redirected.
      $this->assertEquals($response_url, $response->getTargetUrl());
      $this->assertEquals(
        $config_factory->get('redirect.settings')->get('default_status_code'),
        $response->getStatusCode()
      );
    }
    else {
      $this->assertNull($event->getResponse());
    }
  }

  /**
   * Gets response event object.
   *
   * @param $path_info
   *   The path info.
   * @param $query_string
   *   The query string in the url.
   *
   * @return GetResponseEvent
   *   The response for the request.
   */
  protected function getGetResponseEventStub($path_info, $query_string) {
    $request = Request::create($path_info . '?' . $query_string, 'GET', [], [], [], ['SCRIPT_NAME' => 'index.php']);

    $http_kernel = $this->getMockBuilder(HttpKernelInterface::class)
      ->getMock();
    return new GetResponseEvent($http_kernel, $request, 'test');
  }

  /**
   * Data provider for the domain redirects.
   *
   * @return array
   *   An array of requests and expected responses for the redirect domains.
   */
  public function providerDomains() {
    $datasets = [];
    $datasets[] = ['http://foo.com/example', 'http://bar.com/example'];
    $datasets[] = ['http://example.com/foo/test/bar', 'http://example.com/bar/foo'];
    $datasets[] = ['http://simpleexample.com/redirect', 'http://redirected.com/redirect'];
    $datasets[] = ['http://nonexisting.com', NULL];
    $datasets[] = ['http://simpleexample.com/wrongpath', NULL];
    $datasets[] = ['http://foo.com/fixedredirect', 'http://bar.com/fixedredirect'];
    return $datasets;
  }
}
