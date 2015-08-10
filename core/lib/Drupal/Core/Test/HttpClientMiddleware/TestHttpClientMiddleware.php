<?php

/**
 * @file
 * Contains \Drupal\Core\Test\HttpClientMiddleware\TestHttpClientMiddleware.
 */

namespace Drupal\Core\Test\HttpClientMiddleware;

use Psr\Http\Message\RequestInterface;

/**
 * Overrides the User-Agent HTTP header for outbound HTTP requests.
 */
class TestHttpClientMiddleware {

  /**
   * {@inheritdoc}
   *
   * HTTP middleware that replaces the user agent for simpletest requests.
   */
  public function __invoke() {
    // If the database prefix is being used by SimpleTest to run the tests in a copied
    // database then set the user-agent header to the database prefix so that any
    // calls to other Drupal pages will run the SimpleTest prefixed database. The
    // user-agent is used to ensure that multiple testing sessions running at the
    // same time won't interfere with each other as they would if the database
    // prefix were stored statically in a file or database variable.
    return function ($handler) {
      return function (RequestInterface $request, array $options) use ($handler) {
        if ($test_prefix = drupal_valid_test_ua()) {
          $request = $request->withHeader('User-Agent', drupal_generate_test_ua($test_prefix));
        }
        return $handler($request, $options);
      };
    };
  }

}
