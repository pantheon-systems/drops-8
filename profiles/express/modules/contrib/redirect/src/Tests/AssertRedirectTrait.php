<?php

namespace Drupal\redirect\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Url;

/**
 * Asserts the redirect from a given path to the expected destination path.
 */
trait AssertRedirectTrait {

  /**
   * Asserts the redirect from $path to the $expected_ending_url.
   *
   * @param string $path
   *   The request path.
   * @param $expected_ending_url
   *   The path where we expect it to redirect. If NULL value provided, no
   *   redirect is expected.
   * @param string $expected_ending_status
   *   The status we expect to get with the first request.
   */
  public function assertRedirect($path, $expected_ending_url, $expected_ending_status = 'HTTP/1.1 301 Moved Permanently') {
    $this->drupalHead($path);
    $headers = $this->drupalGetHeaders(TRUE);

    $ending_url = isset($headers[0]['location']) ? $headers[0]['location'] : NULL;
    $message = SafeMarkup::format('Testing redirect from %from to %to. Ending url: %url', [
      '%from' => $path,
      '%to' => $expected_ending_url,
      '%url' => $ending_url,
    ]);

    if ($expected_ending_url == '<front>') {
      $expected_ending_url = Url::fromUri('base:')->setAbsolute()->toString();
    }
    elseif (!empty($expected_ending_url)) {
      // Check for absolute/external urls.
      if (!parse_url($expected_ending_url, PHP_URL_SCHEME)) {
        $expected_ending_url = Url::fromUri('base:' . $expected_ending_url)->setAbsolute()->toString();
      }
    }
    else {
      $expected_ending_url = NULL;
    }

    $this->assertEqual($expected_ending_url, $ending_url, $message);

    $this->assertEqual($headers[0][':status'], $expected_ending_status);
  }

}
