<?php

namespace Drupal\webform_test_handler_remote_post;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Random;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

/**
 * Extend Guzzle client so that we can override remote posts.
 */
class WebformTestHandlerRemotePostClient extends Client {

  /**
   * {@inheritdoc}
   */
  public function request($method, $uri = '', array $options = []) {
    if (strpos($uri, 'http://webform-test-handler-remote-post/') === FALSE) {
      return parent::request($method, $uri, $options);
    }

    if ($method == 'get') {
      parse_str(parse_url($uri, PHP_URL_QUERY), $params);
    }
    else {
      $params = (isset($options['json'])) ? $options['json'] : $options['form_params'];
    }

    $response_type = (isset($params['response_type'])) ? $params['response_type'] : 200;
    $operation = ltrim(parse_url($uri, PHP_URL_PATH), '/');
    $random = new Random();
    // Handle 404 errors.
    switch ($response_type) {
      // 404 Not Found.
      case 404:
        return new Response(404, [], 'File not found');

      // 401 Unauthorized.
      case 401:
        $status = 401;
        $headers = ['Content-Type' => ['application/json']];
        $json = [
          'method' => $method,
          'status' => 'unauthorized',
          'message' => (string) new FormattableMarkup('Unauthorized to process @type request.', ['@type' => $operation]),
          'options' => $options,
        ];
        return new Response($status, $headers, Json::encode($json));

      // 500 Internal Server Error.
      case 500:
        $status = 500;
        $headers = ['Content-Type' => ['application/json']];
        $json = [
          'method' => $method,
          'status' => 'fail',
          'message' => (string) new FormattableMarkup('Failed to process @type request.', ['@type' => $operation]),
          'options' => $options,
        ];
        return new Response($status, $headers, Json::encode($json));

      // 200 OK.
      case 200:
      default:
        $status = 200;
        $headers = ['Content-Type' => ['application/json']];
        $json = [
          'method' => $method,
          'status' => 'success',
          'message' => (string) new FormattableMarkup('Processed @type request.', ['@type' => $operation]),
          'options' => $options,
          'confirmation_number' => $random->name(20, TRUE),
        ];
        return new Response($status, $headers, Json::encode($json));
    }
  }

}
