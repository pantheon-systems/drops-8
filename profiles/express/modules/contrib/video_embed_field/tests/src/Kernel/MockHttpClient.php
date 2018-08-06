<?php

namespace Drupal\Tests\video_embed_field\Kernel;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\RequestInterface;

/**
 * An exceptional HTTP client mock.
 */
class MockHttpClient implements ClientInterface {

  /**
   * An exception message for the client methods.
   */
  const EXCEPTION_MESSAGE = "The HTTP mock can't do anything.";

  /**
   * {@inheritdoc}
   */
  public function send(RequestInterface $request, array $options = []) {
    throw new \Exception(static::EXCEPTION_MESSAGE);
  }

  /**
   * {@inheritdoc}
   */
  public function sendAsync(RequestInterface $request, array $options = []) {
    throw new \Exception(static::EXCEPTION_MESSAGE);
  }

  /**
   * {@inheritdoc}
   */
  public function request($method, $uri, array $options = []) {
    throw new \Exception(static::EXCEPTION_MESSAGE);
  }

  /**
   * {@inheritdoc}
   */
  public function requestAsync($method, $uri, array $options = []) {
    throw new \Exception(static::EXCEPTION_MESSAGE);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig($option = NULL) {
    throw new \Exception(static::EXCEPTION_MESSAGE);
  }

  /**
   * Patch up a magic method call.
   */
  public function head($url) {
    throw new \Exception(static::EXCEPTION_MESSAGE);
  }

}
