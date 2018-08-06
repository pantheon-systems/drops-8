<?php

namespace Drupal\webform_test_handler_remote_post;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Site\Settings;

/**
 * Extend Drupal client so that we can override Guzzel client.
 *
 * @see \Drupal\Core\Http\ClientFactory::fromOptions
 */
class WebformTestHandlerRemotePostClientFactory extends ClientFactory {

  /**
   * {@inheritdoc}
   */
  public function fromOptions(array $config = []) {
    $default_config = [
      'verify' => TRUE,
      'timeout' => 30,
      'headers' => [
        'User-Agent' => 'Drupal/' . \Drupal::VERSION . ' (+https://www.drupal.org/) ' . \GuzzleHttp\default_user_agent(),
      ],
      'handler' => $this->stack,
      'proxy' => [
        'http' => NULL,
        'https' => NULL,
        'no' => [],
      ],
    ];
    $config = NestedArray::mergeDeep($default_config, Settings::get('http_client_config', []), $config);
    return new WebformTestHandlerRemotePostClient($config);
  }

}
