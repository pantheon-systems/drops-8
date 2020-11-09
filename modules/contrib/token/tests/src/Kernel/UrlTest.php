<?php

namespace Drupal\Tests\token\Kernel;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test generic url token replacements.
 *
 * @group token
 */
class UrlTest extends KernelTestBase {

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The current request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->token = $this->container->get('token');
    $this->requestStack = $this->container->get('request_stack');
    $this->currentRouteMatch = $this->container->get('current_route_match');
  }

  /**
   * Test the url token replacements for current requests.
   *
   * The method ::expectedCurrentRequestUrlResults() is not declared
   * as a regular data provider, because it might use services from
   * the global Drupal container, which is not initialized yet during
   * the invocation of data providers.
   */
  public function testCurrentRequestUrls() {
    foreach ($this->expectedCurrentRequestUrlResults() as $data_set) {
      list ($request, $text, $data, $options, $expected_output) = $data_set;
      // Set the request as the current one.
      $this->requestStack->pop();
      $this->requestStack->push($request);
      $this->currentRouteMatch->resetRouteMatch();

      $this->assertEquals($expected_output, $this->token->replace($text, $data, $options));
    }
  }

  /**
   * Provides a list of results to expect for ::testRequestUrls().
   *
   * Each data set of this array holds the following order:
   *   - The request object to test for.
   *   - The input text as string.
   *   - The token data as array.
   *   - Further options for the token replacement as array.
   *   - The output to expect after token replacement.
   *
   * @return array
   *   The list of results to expect.
   */
  public function expectedCurrentRequestUrlResults() {
    return [
      [Request::createFromGlobals(), '[current-page:url]', [], [], Url::createFromRequest(Request::createFromGlobals())->setAbsolute()->toString()],
      [Request::create('/should-not-exist'), '[current-page:url:path]', [], [], '/'],
      [Request::create('/https://drupal.org/'), '[current-page:url:absolute]', [], [], '[current-page:url:absolute]'],
      [Request::create('/https://drupal.org/'), '[current-page:url:absolute]', [], ['clear' => TRUE], ''],
    ];
  }

}
