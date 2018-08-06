<?php

namespace Drupal\Tests\redirect\Unit;

use Drupal\Core\Language\Language;
use Drupal\redirect\EventSubscriber\RedirectRequestSubscriber;
use Drupal\Tests\UnitTestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

/**
 * Tests the redirect logic.
 *
 * @group redirect
 *
 * @coversDefaultClass Drupal\redirect\EventSubscriber\RedirectRequestSubscriber
 */
class RedirectRequestSubscriberTest extends UnitTestCase {

  /**
   * @covers ::onKernelRequestCheckRedirect
   */
  public function testRedirectLogicWithQueryRetaining() {

    // The request query.
    $request_query = array('key' => 'val');
    // The query defined by the redirect entity.
    $redirect_query = array('dummy' => 'value');
    // The expected final query. This query must contain values defined
    // by the redirect entity and values from the accessed url.
    $final_query = $redirect_query + $request_query;

    $url = $this->getMockBuilder('Drupal\Core\Url')
      ->disableOriginalConstructor()
      ->getMock();

    $url->expects($this->once())
      ->method('setAbsolute')
      ->with(TRUE)
      ->willReturn($url);

    $url->expects($this->once())
      ->method('getOption')
      ->with('query')
      ->willReturn($redirect_query);

    $url->expects($this->once())
      ->method('setOption')
      ->with('query', $final_query);

    $url->expects($this->once())
      ->method('toString')
      ->willReturn('/test-path');

    $redirect = $this->getRedirectStub($url);
    $event = $this->callOnKernelRequestCheckRedirect($redirect, $request_query, TRUE);

    $this->assertTrue($event->getResponse() instanceof RedirectResponse);
    $response = $event->getResponse();
    $this->assertEquals('/test-path', $response->getTargetUrl());
    $this->assertEquals(301, $response->getStatusCode());
    $this->assertEquals(1, $response->headers->get('X-Redirect-ID'));
  }

  /**
   * @covers ::onKernelRequestCheckRedirect
   */
  public function testRedirectLogicWithoutQueryRetaining() {

    // The request query.
    $request_query = array('key' => 'val');

    $url = $this->getMockBuilder('Drupal\Core\Url')
      ->disableOriginalConstructor()
      ->getMock();

    $url->expects($this->once())
      ->method('setAbsolute')
      ->with(TRUE)
      ->willReturn($url);

    // No query retaining, so getOption should not be called.
    $url->expects($this->never())
      ->method('getOption');
    $url->expects($this->never())
      ->method('setOption');

    $url->expects($this->once())
      ->method('toString')
      ->willReturn('/test-path');

    $redirect = $this->getRedirectStub($url);
    $event = $this->callOnKernelRequestCheckRedirect($redirect, $request_query, FALSE);

    $this->assertTrue($event->getResponse() instanceof RedirectResponse);
    $response = $event->getResponse();
    $this->assertEquals('/test-path', $response->getTargetUrl());
    $this->assertEquals(301, $response->getStatusCode());
    $this->assertEquals(1, $response->headers->get('X-Redirect-ID'));
  }

  /**
   * Instantiates the subscriber and runs onKernelRequestCheckRedirect()
   *
   * @param $redirect
   *   The redirect entity.
   * @param array $request_query
   *   The query that is supposed to come via request.
   * @param bool $retain_query
   *   Flag if to retain the query through the redirect.
   *
   * @return \Symfony\Component\HttpKernel\Event\GetResponseEvent
   *   THe response event.
   */
  protected function callOnKernelRequestCheckRedirect($redirect, $request_query, $retain_query) {

    $event = $this->getGetResponseEventStub('non-existing', http_build_query($request_query));
    $request = $event->getRequest();

    $checker = $this->getMockBuilder('Drupal\redirect\RedirectChecker')
      ->disableOriginalConstructor()
      ->getMock();
    $checker->expects($this->any())
      ->method('canRedirect')
      ->will($this->returnValue(TRUE));
    $checker->expects($this->any())
      ->method('isLoop')
      ->will($this->returnValue(FALSE));

    $context = $this->getMock('Symfony\Component\Routing\RequestContext');

    $inbound_path_processor = $this->getMockBuilder('Drupal\Core\PathProcessor\InboundPathProcessorInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $inbound_path_processor->expects($this->any())
      ->method('processInbound')
      ->with($request->getPathInfo(), $request)
      ->will($this->returnValue($request->getPathInfo()));

    $alias_manager = $this->getMockBuilder('Drupal\Core\Path\AliasManager')
      ->disableOriginalConstructor()
      ->getMock();
    $module_handler = $this->getMockBuilder('Drupal\Core\Extension\ModuleHandlerInterface')
      ->getMock();
    $entity_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityManagerInterface')
      ->getMock();

    $subscriber = new RedirectRequestSubscriber(
      $this->getRedirectRepositoryStub('findMatchingRedirect', $redirect),
      $this->getLanguageManagerStub(),
      $this->getConfigFactoryStub(array('redirect.settings' => array('passthrough_querystring' => $retain_query))),
      $alias_manager,
      $module_handler,
      $entity_manager,
      $checker,
      $context,
      $inbound_path_processor
    );

    // Run the main redirect method.
    $subscriber->onKernelRequestCheckRedirect($event);
    return $event;
  }

  /**
   * Gets the redirect repository mock object.
   *
   * @param $method
   *   Method to mock - either load() or findMatchingRedirect().
   * @param $redirect
   *   The redirect object to be returned.
   *
   * @return PHPUnit_Framework_MockObject_MockObject
   *   The redirect repository.
   */
  protected function getRedirectRepositoryStub($method, $redirect) {
    $repository = $this->getMockBuilder('Drupal\redirect\RedirectRepository')
      ->disableOriginalConstructor()
      ->getMock();

    $repository->expects($this->any())
      ->method($method)
      ->will($this->returnValue($redirect));

    return $repository;
  }

  /**
   * Gets the redirect mock object.
   *
   * @param $url
   *   Url to be returned from getRedirectUrl
   * @param int $status_code
   *   The redirect status code.
   *
   * @return PHPUnit_Framework_MockObject_MockObject
   *   The mocked redirect object.
   */
  protected function getRedirectStub($url, $status_code = 301) {
    $redirect = $this->getMockBuilder('Drupal\redirect\Entity\Redirect')
      ->disableOriginalConstructor()
      ->getMock();
    $redirect->expects($this->once())
      ->method('getRedirectUrl')
      ->will($this->returnValue($url));
    $redirect->expects($this->any())
      ->method('getStatusCode')
      ->will($this->returnValue($status_code));
    $redirect->expects($this->any())
      ->method('id')
      ->willReturn(1);
    $redirect->expects($this->once())
      ->method('getCacheTags')
      ->willReturn(['redirect:1']);

    return $redirect;
  }

  /**
   * Gets post response event.
   *
   * @param array $headers
   *   Headers to be set into the response.
   *
   * @return \Symfony\Component\HttpKernel\Event\PostResponseEvent
   *   The post response event object.
   */
  protected function getPostResponseEvent($headers = array()) {
    $http_kernel = $this->getMockBuilder('\Symfony\Component\HttpKernel\HttpKernelInterface')
      ->getMock();
    $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
      ->disableOriginalConstructor()
      ->getMock();

    $response = new Response('', 301, $headers);

    return new PostResponseEvent($http_kernel, $request, $response);
  }

  /**
   * Gets response event object.
   *
   * @param $path_info
   * @param $query_string
   *
   * @return GetResponseEvent
   */
  protected function getGetResponseEventStub($path_info, $query_string) {
    $request = Request::create($path_info . '?' . $query_string, 'GET', [], [], [], ['SCRIPT_NAME' => 'index.php']);

    $http_kernel = $this->getMockBuilder('\Symfony\Component\HttpKernel\HttpKernelInterface')
      ->getMock();
    return new GetResponseEvent($http_kernel, $request, 'test');
  }

  /**
   * Gets the language manager mock object.
   *
   * @return \Drupal\language\ConfigurableLanguageManagerInterface|PHPUnit_Framework_MockObject_MockObject
   */
  protected function getLanguageManagerStub() {
    $language_manager = $this->getMockBuilder('Drupal\language\ConfigurableLanguageManagerInterface')
      ->getMock();
    $language_manager->expects($this->any())
      ->method('getCurrentLanguage')
      ->will($this->returnValue(new Language(array('id' => 'en'))));

    return $language_manager;
  }

}
